// 隨機選擇背景圖片
function getBackgroundByRarity(rarity) {
    const backgroundFiles = {
        'Common': {
            path: '../images/card_background/normal/',
            files: ['darkblue.png', 'darkgreen.png', 'lightblue.png', 'pink.png', 'red.png', 'tiffany.png', 'violet.png']
        },
        'Baby':  {
            path: '../images/card_background/rare/',
            files: ['blackblue.png', 'blue.png', 'bluepurple.png', 'colorful.png', 'metalpurple.png', 'pink.png', 'razer.png', 'red.png', 'silver.png', 'whiteblue.png']
        },
        'Lengendary': {
            path: '../images/card_background/rare/',
            files: ['blackblue.png', 'blue.png', 'bluepurple.png', 'colorful.png', 'metalpurple.png', 'pink.png', 'razer.png', 'red.png', 'silver.png', 'whiteblue.png']
        },
        'Mythical': {
            path: '../images/card_background/rare/',
            files: ['blackblue.png', 'blue.png', 'bluepurple.png', 'colorful.png', 'metalpurple.png', 'pink.png', 'razer.png', 'red.png', 'silver.png', 'whiteblue.png']
        }
    };

    const config = backgroundFiles[rarity] || backgroundFiles['Common'];
    const randomFile = config.files[Math.floor(Math.random() * config.files.length)];
    return config.path + randomFile;
}

// 繪製卡片
async function drawCardDataURL(cardData, width, height) {
    const canvas = document.createElement('canvas');
    canvas.width = width;
    canvas.height = height;
    const ctx = canvas.getContext('2d');

    try {
        ctx.save();
        
        // 先繪製圓角路徑
        ctx.beginPath();
        ctx.roundRect(0, 0, width, height, 20); // 使用新的 roundRect API
        ctx.clip();

        // 繪製背景圖片
        const backgroundImage = await loadImage(getBackgroundByRarity(cardData.Rarity));
        ctx.drawImage(backgroundImage, 0, 0, width, height);

        // 繪製內部白色區域
        const margin = width * 0.05;
        ctx.beginPath();
        ctx.roundRect(margin, margin, width - margin * 2, height - margin * 2, 15);
        ctx.fillStyle = 'rgba(255, 255, 255, 0.98)';
        ctx.fill();

        // 繪製寶可夢圖片和文字
        const pokemonImage = await loadImage(cardData.image_url);
        const pokeW = width * 0.7;
        const pokeH = height * 0.35;
        const pokeX = (width - pokeW) / 2;
        const pokeY = height * 0.12;
        ctx.drawImage(pokemonImage, pokeX, pokeY, pokeW, pokeH);

        drawCardText(ctx, canvas, cardData, pokeX, pokeY, pokeW, pokeH);
        
        ctx.restore();

        return canvas.toDataURL('image/png');
    } catch (error) {
        console.error('Error in drawCardDataURL:', error);
        throw error;
    }
}

// 其他輔助函數保持不變...
function drawCardText(ctx, canvas, card, pokeX, pokeY, pokeW, pokeH) {
    let y = pokeY + pokeH + canvas.height * 0.05;

    // 名稱
    ctx.textAlign = 'left';
    ctx.fillStyle = '#333';
    ctx.font = `bold ${canvas.width * 0.08}px "Cinzel", "Noto Sans TC", serif`;
    const xName = canvas.width * 0.18;
    ctx.fillText(card.pokemon_name || '', xName, y);

    // 分隔線
    y += canvas.height * 0.02;
    ctx.beginPath();
    ctx.strokeStyle = '#ddd';
    ctx.lineWidth = 1;
    ctx.moveTo(xName, y);
    ctx.lineTo(canvas.width * 0.82, y);
    ctx.stroke();

    // 稀有度
    y += canvas.height * 0.04;
    ctx.font = `${canvas.width * 0.05}px "Cinzel", "Noto Sans TC", serif`;
    ctx.fillText('稀有度', xName, y);

    ctx.font = `bold ${canvas.width * 0.05}px "Cinzel", "Noto Sans TC", serif`;
    ctx.fillText(card.rarity || '', xName + canvas.width * 0.15, y);

    // 屬性
    if (card.type1) {
        y += canvas.height * 0.04;
        ctx.font = `${canvas.width * 0.05}px "Cinzel", "Noto Sans TC", serif`;
        ctx.fillText('屬性', xName, y);

        ctx.font = `bold ${canvas.width * 0.05}px "Cinzel", "Noto Sans TC", serif`;
        const types = card.type2 
            ? `${card.type1} / ${card.type2}`
            : card.type1;
        ctx.fillText(types, xName + canvas.width * 0.15, y);
    }

    // 特性
    if (card.Ability) {
        y += canvas.height * 0.04;
        ctx.font = `${canvas.width * 0.05}px "Cinzel", "Noto Sans TC", serif`;
        ctx.fillText('特性', xName, y);

        ctx.font = `bold ${canvas.width * 0.05}px "Cinzel", "Noto Sans TC", serif`;
        ctx.fillText(card.Ability, xName + canvas.width * 0.15, y);

        if (card.Description) {
            y += canvas.height * 0.04;
            ctx.font = `${canvas.width * 0.04}px "Cinzel", "Noto Sans TC", serif`;
            const maxWidth = canvas.width * 0.64;
            wrapText(ctx, card.Description, xName, y, maxWidth, canvas.height * 0.05);
        }
    }
}

// 輔助函數：文字換行
function wrapText(ctx, text, x, y, maxWidth, lineHeight) {
    const words = text.split('');
    let line = '';
    for (let n = 0; n < words.length; n++) {
        const testLine = line + words[n];
        const metrics = ctx.measureText(testLine);
        const testWidth = metrics.width;
        if (testWidth > maxWidth && n > 0) {
            ctx.fillText(line, x, y);
            line = words[n];
            y += lineHeight;
        } else {
            line = testLine;
        }
    }
    ctx.fillText(line, x, y);
}

function loadImage(url) {
    return new Promise((resolve, reject) => {
        const img = new Image();
        img.onload = () => resolve(img);
        img.onerror = () => reject(new Error(`Failed to load image: ${url}`));
        img.src = url;
    });
}

function reDrawSingleCard() {
    // ... 保持原有代碼 ...
}

function reDrawCards() {
}

// 頁面載入後的處理
document.addEventListener('DOMContentLoaded', function() {
    const phpCardsElement = document.getElementById('php-cards-data');
    if (phpCardsElement) {
        const phpCards = JSON.parse(phpCardsElement.value);
        console.log('PHP Cards:', phpCards);
        if (phpCards && phpCards.length > 0) {
            processAndDrawCards(phpCards);
        }
    }
    
    // 處理抽卡按鈕點擊
    document.querySelectorAll('.pack-button').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            drawCard();
        });
    });

    // 處理加入卡冊按鈕點擊
    document.querySelector('.collection-button')?.addEventListener('click', function() {
        if (!window.cardData?.cards) return;
        addToCollection(window.cardData.cards);
    });

    // 處理再抽一次按鈕點擊
    document.querySelector('.draw-button')?.addEventListener('click', function() {
        drawCard();
    });
});

// 抽卡相關函數
function drawCard() {
    const userConfirmed = confirm('確定要花10皮卡幣抽卡嗎?');
    if (!userConfirmed) return;

    fetch('pakage.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: 'action=draw_card'
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        console.log('Response data:', data);
        if (data.cards) {
            // 修改圖片檔案名稱格式
            data.cards = data.cards.map(card => ({
                ...card,
                image_url: `../images/pokemon_images/${card.pokemon_name.toLowerCase().replace(/ /g, '-')}.png`
            }));
        }
        handleDrawCardResponse(data);
    })
    .catch(error => {
        console.error('抽卡發生錯誤:', error);
        handleDrawCardError(error);
    });
}

function formatImageUrl(pokemonName) {
    return `../images/pokemon_images/${pokemonName.toLowerCase().replace(/ /g, '-')}.png`;
}

function handleDrawCardResponse(data) {
    if (!data) {
        console.error('No data received');
        alert('抽卡失敗：伺服器無回應');
        return;
    }

    if (data.success) {
        document.getElementById('coin-amount').textContent = data.new_balance;
        document.getElementById('packContainer').style.display = 'none';
        document.getElementById('cardResults').style.display = 'block';
        
        window.cardData = {
            cards: data.cards.map(card => ({
                pokemon_name: card.pokemon_name,
                type1: card.type1 || '',
                type2: card.type2 || '',
                image_url: formatImageUrl(card.pokemon_name),
                rarity: card.rarity
            }))
        };
        
        if (data.cards && data.cards.length > 0) {
            processAndDrawCards(data.cards);
        }
    } else {
        alert(data.message || '抽卡失敗');
    }
}

function handleDrawCardError(error) {
    console.error('抽卡時發生錯誤:', error);
    alert('發生錯誤，請稍後再試');
}

function addToCollection(cards) {
    // 獲取每張卡片的背景圖片 URL
    const formattedCards = cards.map(card => ({
        pokemon_name: card.pokemon_name,
        backgroundUrl: getBackgroundByRarity(card.rarity) // 使用相同的背景生成函數
    }));

    console.log('Sending cards data:', formattedCards); // 用於調試

    fetch('pakage.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: 'action=add_to_booklet&cards=' + encodeURIComponent(JSON.stringify(formattedCards))
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('成功加入卡冊！');
        } else {
            alert('加入卡冊失敗：' + data.message);
        }
    })
    .catch(error => {
        console.error('加入卡冊���發生錯誤:', error);
        alert('發生錯誤，請稍後再試');
    });
}

// 添加 processAndDrawCards 函數
async function processAndDrawCards(cards) {
    const cardContainer = document.querySelector('.card-container');
    cardContainer.innerHTML = '';

    for (const card of cards) {
        const cardItem = document.createElement('div');
        cardItem.className = 'card-item';
        
        // 修改卡片 HTML 結構，確保背面圖片有圓角
        cardItem.innerHTML = `
            <div class="card-inner">
                <div class="card-back">
                    <img src="../images/card_back.png" alt="card back" style="width: 100%; height: 100%; object-fit: cover; border-radius: 20px;">
                </div>
                <div class="card-front">
                    <img class="card-image" style="width: 100%; height: 100%; object-fit: contain; border-radius: 20px;">
                </div>
            </div>
        `;

        cardContainer.appendChild(cardItem);

        try {
            const dataUrl = await drawCardDataURL(card, 250, 350);
            const cardImage = cardItem.querySelector('.card-image');
            cardImage.src = dataUrl;
        } catch (error) {
            console.error('Error drawing card:', error);
        }

        // 添加點擊事件
        cardItem.addEventListener('click', function() {
            const cardInner = this.querySelector('.card-inner');
            cardInner.style.borderRadius = '20px'; // 確保翻轉時保持圓角
            cardInner.classList.add('card-flipped');
        });
    }

    document.getElementById('cardResults').style.display = 'block';
}

// 如果瀏覽器不支援 roundRect，添加這個 polyfill
if (!CanvasRenderingContext2D.prototype.roundRect) {
    CanvasRenderingContext2D.prototype.roundRect = function(x, y, width, height, radius) {
        if (width < 2 * radius) radius = width / 2;
        if (height < 2 * radius) radius = height / 2;
        this.beginPath();
        this.moveTo(x + radius, y);
        this.arcTo(x + width, y, x + width, y + height, radius);
        this.arcTo(x + width, y + height, x, y + height, radius);
        this.arcTo(x, y + height, x, y, radius);
        this.arcTo(x, y, x + width, y, radius);
        this.closePath();
        return this;
    };
}
