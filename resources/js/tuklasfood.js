const cards = [...document.querySelectorAll('[data-recipe]')];
import './cooking-chat';
const search = document.querySelector('[data-recipe-search]');
let activeFilter = 'all';

function filterRecipes() {
    const query = search?.value.trim().toLowerCase() || '';
    let count = 0;
    cards.forEach(card => {
        const matchesFilter = activeFilter === 'all' || (activeFilter === 'easy' && card.dataset.difficulty === 'easy') || (activeFilter === 'quick' && Number(card.dataset.minutes) <= 30);
        card.hidden = !matchesFilter || !card.dataset.title.includes(query);
        if (!card.hidden) count++;
    });
    const empty = document.querySelector('[data-empty-recipes]');
    if (empty) empty.hidden = count > 0;
}

search?.addEventListener('input', filterRecipes);
document.querySelectorAll('[data-filter]').forEach(button => button.addEventListener('click', () => {
    activeFilter = button.dataset.filter;
    document.querySelectorAll('[data-filter]').forEach(item => {
        item.classList.toggle('active', item === button);
        item.setAttribute('aria-pressed', String(item === button));
    });
    filterRecipes();
}));

document.querySelectorAll('[data-open]').forEach(button => button.addEventListener('click', () => {
    document.getElementById(button.dataset.open)?.showModal();
}));
document.querySelectorAll('dialog').forEach(dialog => dialog.addEventListener('click', event => {
    const bounds = dialog.getBoundingClientRect();
    if (event.target === dialog && (event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom)) dialog.close();
}));
