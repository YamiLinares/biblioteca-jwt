const apiRoot = '/api';
const categoryNames = {
    literatura: 'Literatura', ciencia: 'Ciencia', tecnologia: 'Tecnología',
    historia: 'Historia', educacion: 'Educación', 'desarrollo-personal': 'Desarrollo personal',
};
let accessToken = null;
let currentUser = null;
let editingBookId = null;
let deletingBookId = null;
let searchTimer = null;

const byId = (id) => document.getElementById(id);
const safeText = (value) => String(value ?? '');

async function apiRequest(path, options = {}) {
    const headers = { Accept: 'application/json', ...(options.headers || {}) };
    if (options.body !== undefined) headers['Content-Type'] = 'application/json';
    if (accessToken) headers.Authorization = `Bearer ${accessToken}`;

    let response;
    try {
        response = await fetch(`${apiRoot}${path}`, {
            method: options.method || 'GET',
            headers,
            body: options.body === undefined ? undefined : JSON.stringify(options.body),
        });
    } catch {
        throw { message: 'No se pudo conectar con la biblioteca. Comprueba que el servidor esté activo.' };
    }

    const payload = response.status === 204 ? null : await response.json().catch(() => ({}));
    if (!response.ok) {
        const error = { status: response.status, ...payload };
        if (response.status === 401 && accessToken && !path.startsWith('/auth/login')) {
            accessToken = null;
            currentUser = null;
            navigate('login', true);
            showToast('La sesión terminó. Inicia sesión nuevamente.', 'error');
        }
        throw error;
    }

    return payload;
}

function validationMessage(error) {
    const messages = error?.errors ? Object.values(error.errors).flat() : [];
    return messages[0] || error?.message || 'Ocurrió un error. Inténtalo nuevamente.';
}

function currentRoute() {
    const hashRoute = window.location.hash.replace(/^#\/?/, '');
    if (hashRoute) return hashRoute;
    if (window.location.pathname === '/register') return 'register';
    if (window.location.pathname === '/login') return 'login';
    return accessToken ? 'dashboard' : 'login';
}

function navigate(page, replace = false) {
    const target = `#/${page}`;
    if (replace) history.replaceState(null, '', target);
    else if (window.location.hash !== target) window.location.hash = target;
    renderRoute();
}

function renderRoute() {
    const route = currentRoute();
    const protectedPages = ['dashboard', 'catalog', 'profile'];
    if (protectedPages.includes(route) && !accessToken) {
        navigate('login', true);
        return;
    }

    const authRoute = route === 'login' || route === 'register';
    byId('auth-shell').classList.toggle('hidden', !authRoute);
    byId('app-shell').classList.toggle('hidden', authRoute);
    byId('login-panel').classList.toggle('hidden', route !== 'login');
    byId('register-panel').classList.toggle('hidden', route !== 'register');

    if (authRoute) return;

    const page = protectedPages.includes(route) ? route : 'dashboard';
    for (const name of protectedPages) byId(`${name}-page`).classList.toggle('hidden', page !== name);
    byId('breadcrumb-current').textContent = ({ dashboard: 'Resumen', catalog: 'Catálogo', profile: 'Mi perfil' })[page];
    document.querySelectorAll('[data-nav]').forEach((link) => link.classList.toggle('active', link.dataset.nav === page));
    updateIdentity();

    if (page === 'dashboard') loadDashboard();
    if (page === 'catalog') loadCatalog();
    if (page === 'profile') loadProfile();
}

function updateIdentity() {
    if (!currentUser) return;
    const initials = safeText(currentUser.name).trim().split(/\s+/).slice(0, 2).map((part) => part[0]).join('').toUpperCase();
    byId('sidebar-name').textContent = currentUser.name;
    byId('sidebar-initials').textContent = initials || 'A';
    byId('header-avatar').textContent = initials || 'A';
    byId('profile-avatar').textContent = initials || 'A';
    byId('welcome-name').textContent = currentUser.name.split(' ')[0];
}

function showToast(message, kind = 'success') {
    const toast = byId('toast');
    toast.textContent = message;
    toast.className = `toast visible ${kind}`;
    window.clearTimeout(showToast.timeout);
    showToast.timeout = window.setTimeout(() => toast.classList.remove('visible'), 3600);
}

function createCell(content, className = '') {
    const cell = document.createElement('td');
    if (className) cell.className = className;
    if (content instanceof Node) cell.append(content);
    else cell.textContent = safeText(content);
    return cell;
}

function statusBadge(book) {
    const badge = document.createElement('span');
    badge.className = `status-badge ${book.stock > 0 ? 'available' : 'unavailable'}`;
    const dot = document.createElement('i');
    badge.append(dot, document.createTextNode(book.stock > 0 ? 'Disponible' : 'Agotado'));
    return badge;
}

function titleCell(book, includeIsbn = false) {
    const wrapper = document.createElement('div');
    wrapper.className = 'title-cell';
    const title = document.createElement('strong');
    title.textContent = book.title;
    wrapper.append(title);
    if (includeIsbn) {
        const isbn = document.createElement('small');
        isbn.textContent = `ISBN ${book.isbn}`;
        wrapper.append(isbn);
    }
    return wrapper;
}

async function getBooks(query = '') {
    return apiRequest(`/books?per_page=100${query}`);
}

async function loadDashboard() {
    try {
        const result = await getBooks();
        const books = result.data || [];
        byId('stat-titles').textContent = result.meta?.total ?? books.length;
        byId('stat-stock').textContent = books.reduce((sum, book) => sum + book.stock, 0);
        byId('stat-unavailable').textContent = books.filter((book) => book.stock === 0).length;
        byId('stat-categories').textContent = new Set(books.map((book) => book.category)).size;
        const body = byId('recent-books');
        body.replaceChildren();
        byId('recent-empty').classList.toggle('hidden', books.length > 0);
        for (const book of books.slice(0, 5)) {
            const row = document.createElement('tr');
            row.append(createCell(titleCell(book)), createCell(book.author), createCell(categoryNames[book.category] || book.category), createCell(book.stock), createCell(statusBadge(book)));
            body.append(row);
        }
    } catch (error) {
        showToast(validationMessage(error), 'error');
    }
}

async function loadCatalog() {
    const search = byId('search-books').value.trim();
    const category = byId('filter-category').value;
    const params = new URLSearchParams();
    if (search) params.set('search', search);
    if (category) params.set('category', category);
    params.set('per_page', '100');

    try {
        const result = await apiRequest(`/books?${params.toString()}`);
        const books = result.data || [];
        const body = byId('catalog-books');
        body.replaceChildren();
        byId('catalog-count').textContent = `${result.meta?.total ?? books.length} ${books.length === 1 ? 'título' : 'títulos'}`;
        byId('catalog-empty').classList.toggle('hidden', books.length > 0);
        for (const book of books) {
            const row = document.createElement('tr');
            row.dataset.bookId = book.id;
            row.append(createCell(titleCell(book, true), 'book-title-column'));
            row.append(createCell(book.author));
            row.append(createCell(categoryNames[book.category] || book.category));
            row.append(createCell(book.publication_year));
            row.append(createCell(book.stock));
            row.append(createCell(statusBadge(book)));
            const actions = document.createElement('td');
            actions.className = 'row-actions';
            actions.append(actionButton('detail', book.id, 'Ver detalle', '↗'), actionButton('patch-stock', book.id, 'Actualizar existencias', '±'), actionButton('edit', book.id, 'Editar libro', '✎'), actionButton('delete', book.id, 'Eliminar libro', '×'));
            row.append(actions);
            body.append(row);
        }
    } catch (error) {
        showToast(validationMessage(error), 'error');
    }
}

function actionButton(action, id, label, icon) {
    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'row-action';
    button.dataset.action = action;
    button.dataset.id = id;
    button.setAttribute('aria-label', label);
    button.title = label;
    button.textContent = icon;
    return button;
}

async function loadProfile() {
    try {
        const result = await apiRequest('/auth/me');
        currentUser = result.data;
        updateIdentity();
        byId('profile-name').textContent = currentUser.name;
        byId('profile-email').textContent = currentUser.email;
    } catch (error) {
        if (error.status !== 401) showToast(validationMessage(error), 'error');
    }
}

function openBookDialog(book = null) {
    editingBookId = book?.id || null;
    const form = byId('book-form');
    form.reset();
    byId('book-form-error').textContent = '';
    byId('book-dialog-eyebrow').textContent = book ? 'ACTUALIZAR CATÁLOGO' : 'NUEVO TÍTULO';
    byId('book-dialog-title').textContent = book ? 'Editar libro' : 'Añadir al catálogo';
    byId('book-submit').textContent = book ? 'Guardar cambios' : 'Guardar libro';
    if (book) {
        for (const field of ['title', 'author', 'isbn', 'description', 'category', 'publication_year', 'stock']) {
            form.elements[field].value = book[field] ?? '';
        }
    } else {
        form.elements.stock.value = 1;
    }
    byId('book-dialog').showModal();
}

function openBookDetail(book) {
    byId('detail-title').textContent = book.title;
    const content = byId('detail-content');
    content.replaceChildren();
    const details = [
        ['Autor', book.author], ['ISBN', book.isbn], ['Categoría', categoryNames[book.category] || book.category],
        ['Año de publicación', book.publication_year], ['Ejemplares disponibles', book.stock],
        ['Estado', book.stock > 0 ? 'Disponible' : 'Agotado'], ['Descripción', book.description],
    ];
    for (const [label, value] of details) {
        const item = document.createElement('div');
        item.className = 'detail-row';
        const term = document.createElement('span');
        term.textContent = label;
        const description = document.createElement('strong');
        description.textContent = safeText(value);
        item.append(term, description);
        content.append(item);
    }
    byId('detail-dialog').showModal();
}

async function signOut() {
    try {
        await apiRequest('/auth/logout', { method: 'POST' });
        showToast('Sesión cerrada y token invalidado.');
    } catch (error) {
        if (error.status !== 401) showToast(validationMessage(error), 'error');
    } finally {
        accessToken = null;
        currentUser = null;
        history.replaceState(null, '', '/#/login');
        renderRoute();
    }
}

byId('login-form').addEventListener('submit', async (event) => {
    event.preventDefault();
    byId('login-error').textContent = '';
    const button = event.currentTarget.querySelector('[type="submit"]');
    button.disabled = true;
    try {
        const result = await apiRequest('/auth/login', { method: 'POST', body: { email: byId('login-email').value.trim(), password: byId('login-password').value } });
        accessToken = result.access_token;
        currentUser = result.user;
        navigate('dashboard');
        showToast('Bienvenida, sesión segura iniciada.');
    } catch (error) {
        byId('login-error').textContent = validationMessage(error);
    } finally {
        button.disabled = false;
    }
});

byId('register-form').addEventListener('submit', async (event) => {
    event.preventDefault();
    byId('register-error').textContent = '';
    const button = event.currentTarget.querySelector('[type="submit"]');
    button.disabled = true;
    try {
        const result = await apiRequest('/auth/register', { method: 'POST', body: {
            name: byId('register-name').value.trim(), email: byId('register-email').value.trim(),
            password: byId('register-password').value, password_confirmation: byId('register-confirmation').value,
        } });
        accessToken = result.access_token;
        currentUser = result.user;
        navigate('dashboard');
        showToast('Cuenta creada. Ya puedes explorar la colección.');
    } catch (error) {
        byId('register-error').textContent = validationMessage(error);
    } finally {
        button.disabled = false;
    }
});

byId('book-form').addEventListener('submit', async (event) => {
    event.preventDefault();
    byId('book-form-error').textContent = '';
    const form = new FormData(event.currentTarget);
    const book = Object.fromEntries(form.entries());
    book.publication_year = Number(book.publication_year);
    book.stock = Number(book.stock);
    const button = byId('book-submit');
    button.disabled = true;
    try {
        await apiRequest(editingBookId ? `/books/${editingBookId}` : '/books', { method: editingBookId ? 'PUT' : 'POST', body: book });
        byId('book-dialog').close();
        showToast(editingBookId ? 'Cambios guardados.' : 'Libro añadido al catálogo.');
        if (currentRoute() === 'dashboard') loadDashboard();
        else loadCatalog();
    } catch (error) {
        byId('book-form-error').textContent = validationMessage(error);
    } finally {
        button.disabled = false;
    }
});

document.addEventListener('click', async (event) => {
    const toggle = event.target.closest('[data-toggle-password]');
    if (toggle) {
        const input = byId(toggle.dataset.togglePassword);
        input.type = input.type === 'password' ? 'text' : 'password';
        toggle.textContent = input.type === 'password' ? 'Mostrar' : 'Ocultar';
        return;
    }
    const close = event.target.closest('[data-close-dialog]');
    if (close) byId(close.dataset.closeDialog).close();
    const newBook = event.target.closest('[data-action="new-book"]');
    if (newBook) openBookDialog();
    const action = event.target.closest('[data-action][data-id]');
    if (!action) return;

    const id = action.dataset.id;
    try {
        if (action.dataset.action === 'detail') openBookDetail((await apiRequest(`/books/${id}`)).data);
        if (action.dataset.action === 'edit') openBookDialog((await apiRequest(`/books/${id}`)).data);
        if (action.dataset.action === 'patch-stock') {
            const stock = window.prompt('Ingresa las nuevas existencias (0 o más):');
            if (stock === null) return;
            await apiRequest(`/books/${id}`, { method: 'PATCH', body: { stock: Number(stock) } });
            showToast('Existencias actualizadas.');
            loadCatalog();
        }
        if (action.dataset.action === 'delete') {
            deletingBookId = id;
            byId('confirm-dialog').showModal();
        }
    } catch (error) {
        showToast(validationMessage(error), 'error');
    }
});

byId('confirm-delete').addEventListener('click', async () => {
    if (!deletingBookId) return;
    try {
        await apiRequest(`/books/${deletingBookId}`, { method: 'DELETE' });
        byId('confirm-dialog').close();
        deletingBookId = null;
        showToast('Libro eliminado del catálogo.');
        loadCatalog();
    } catch (error) {
        showToast(validationMessage(error), 'error');
    }
});

byId('search-books').addEventListener('input', () => {
    window.clearTimeout(searchTimer);
    searchTimer = window.setTimeout(loadCatalog, 250);
});
byId('filter-category').addEventListener('change', loadCatalog);
byId('sidebar-logout').addEventListener('click', signOut);
byId('profile-logout').addEventListener('click', signOut);
byId('header-avatar').addEventListener('click', () => navigate('profile'));
byId('mobile-menu').addEventListener('click', () => byId('app-shell').classList.toggle('nav-open'));
window.addEventListener('hashchange', renderRoute);
window.addEventListener('DOMContentLoaded', renderRoute);

renderRoute();
