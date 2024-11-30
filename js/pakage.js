document.addEventListener('DOMContentLoaded', function() {
    const packContainer = document.getElementById('packContainer');
    const cardResults = document.getElementById('cardResults');
    const drawForm = document.getElementById('drawForm');
    
    // 如果有卡片結果，隱藏卡包容器
    if (cardResults && cardResults.children.length > 0) {
        packContainer.style.display = 'none';
        cardResults.style.display = 'block';
        cardResults.classList.add('show');
        initializeCards();
    }

    // 表單提交事件
    if (drawForm) {
        drawForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // 隱藏卡包
            packContainer.style.display = 'none';
            
            // 提交表單
            this.submit();
        });
    }

    // 初始化卡片翻轉功能
    function initializeCards() {
        const cards = document.querySelectorAll('.card-inner');
        
        cards.forEach(card => {
            card.addEventListener('click', function() {
                if (!this.classList.contains('card-flipped')) {
                    this.classList.add('card-flipped');
                }
            });
        });
    }

    // 修改加入卡冊功能
    function addToCollection() {
        const cards = document.querySelectorAll('.card-inner');
        const allCardsFlipped = Array.from(cards).every(card => 
            card.classList.contains('card-flipped')
        );

        if (!allCardsFlipped) {
            showNotification('請先翻開所有卡片！');
            return;
        }

        // 獲取所有卡片數據
        const cardData = [];
        document.querySelectorAll('.card-item').forEach(card => {
            cardData.push({
                name: card.querySelector('.card-name').textContent.trim(),
                rarity: card.querySelector('.card-rarity').textContent.trim(),
                type1: card.querySelector('.card-type').textContent.split('/')[0].trim(),
                type2: card.querySelector('.card-type').textContent.split('/')[1]?.trim() || null,
                image_url: card.querySelector('img').src
            });
        });

        // 發送AJAX請求到服務器
        fetch('../php/booklet_add.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(cardData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('卡片已成功加入卡冊！');
            } else {
                showNotification(data.message || '加入卡冊失敗，請稍後再試。');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('發生錯誤，請稍後再試。');
        });
    }

    // 顯示通知的函數
    function showNotification(message) {
        const notification = document.createElement('div');
        notification.className = 'notification';
        notification.textContent = message;
        document.body.appendChild(notification);

        setTimeout(() => {
            notification.remove();
        }, 3000);
    }

    // 綁定加入卡冊按鈕事件
    const collectionButton = document.querySelector('.collection-button');
    if (collectionButton) {
        collectionButton.addEventListener('click', addToCollection);
    }
});
