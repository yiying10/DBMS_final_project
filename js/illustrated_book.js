document.addEventListener('DOMContentLoaded', function() {
    const searchBox = document.querySelector('.search-box');
    const filterCheckboxes = document.querySelectorAll('.filter-checkbox');
    
    // 處理搜尋
    searchBox.addEventListener('input', filterPokemons);
    
    // 處理篩選選項
    filterCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', filterPokemons);
    });
});

function filterPokemons() {
    const searchTerm = document.querySelector('.search-box').value.toLowerCase();
    const selectedRarity = document.querySelector('input[name="rarity"]:checked').id.split('-')[1];
    const selectedType = document.querySelector('input[name="type"]:checked').id.split('-')[1];
    
    const pokemonCards = document.querySelectorAll('.pokemon-card');
    
    pokemonCards.forEach(card => {
        const name = card.dataset.name.toLowerCase();
        const rarity = card.dataset.rarity;
        const type = card.dataset.type;
        
        const matchesSearch = name.includes(searchTerm);
        const matchesRarity = selectedRarity === 'all' || rarity === selectedRarity;
        const matchesType = selectedType === 'all' || type === selectedType;
        
        card.style.display = matchesSearch && matchesRarity && matchesType ? 'block' : 'none';
    });
}

function redirectToCardGenerator(pokemonName) {
    // 儲存選中的寶可夢
    fetch(`set_selected_pokemon.php?name=${encodeURIComponent(pokemonName)}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = 'generate.php';
            }
        });
}

function showDetails(name, rarity, type1, imageUrl, type2, total) {
    document.getElementById('modalName').textContent = name;
    document.getElementById('modalRarity').textContent = rarity;
    document.getElementById('modalType1').textContent = type1;
    document.getElementById('modalType2').textContent = type2 || '無';
    document.getElementById('modalTotal').textContent = total;
    document.getElementById('modalImage').src = imageUrl;
    document.getElementById('pokemonModal').style.display = 'block';
}

function closeModal() {
    document.getElementById('pokemonModal').style.display = 'none';
}

// 點擊模態框外部關閉
window.onclick = function(event) {
    const modal = document.getElementById('pokemonModal');
    if (event.target == modal) {
        modal.style.display = 'none';
    }
}