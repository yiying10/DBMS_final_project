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

function redirectToCardGenerator(name, rarity, type1, imageUrl, type2) {
    // 將寶可夢資訊存儲到 session
    fetch('set_pokemon_data.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            name: name,
            rarity: rarity,
            type1: type1,
            type2: type2,
            imageUrl: imageUrl
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.href = 'generate.php';
        }
    });
}

function showDetails(name, rarity, type1, imageUrl, type2, total, id) {
    const modal = document.getElementById('pokemonModal');
    
    // 設置模態框內容
    document.getElementById('modalName').textContent = name;
    document.getElementById('modalRarity').textContent = rarity;
    document.getElementById('modalType1').textContent = type1;
    document.getElementById('modalType2').textContent = type2 || '無';
    document.getElementById('modalTotal').textContent = total;
    document.getElementById('modalImage').src = imageUrl;
    
    // 根據 ID 決定是否顯示刪除按鈕
    const deleteButtonContainer = document.getElementById('deleteButtonContainer');
    deleteButtonContainer.innerHTML = ''; // 清空現有內容
    
    if (id > 898) {
        const deleteButton = document.createElement('button');
        deleteButton.className = 'delete-btn';
        deleteButton.textContent = '刪除';
        deleteButton.onclick = () => deletePokemon(id, name);
        deleteButtonContainer.appendChild(deleteButton);
    }
    
    // 顯示模態框
    requestAnimationFrame(() => {
        modal.style.display = 'flex';
        requestAnimationFrame(() => {
            modal.classList.add('show');
        });
    });
}

// 添加刪除功能
function deletePokemon(id, name) {
    if (!confirm('確定要刪除這個寶可夢嗎？此操作將同時刪除相關的能力和描述，且無法撤銷。')) {
        return;
    }

    fetch('../php/delete_pokemon.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            id: id,
            name: name
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('寶可夢已成功刪除！');
            closeModal();
            location.reload(); // 重新載入頁面以更新顯示
        } else {
            alert('刪除失敗：' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('刪除過程中發生錯誤');
    });
}

function closeModal() {
    const modal = document.getElementById('pokemonModal');
    modal.classList.remove('show');
    setTimeout(() => {
        modal.style.display = 'none';
    }, 300);
}

// 點擊模態框外部關閉
window.onclick = function(event) {
    const modal = document.getElementById('pokemonModal');
    if (event.target == modal) {
        closeModal();
    }
}