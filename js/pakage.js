// 隨機選擇背景圖片
function getBackgroundByRarity(rarity) {
    const backgroundFiles = {
        'Common': {
            path: '../images/card_background/normal/',
            files: ['darkblue.png', 'darkgreen.png', 'lightblue.png', 'pink.png', 'red.png', 'tiffany.png', 'violet.png']
        },
        'Baby': {
            path: '../images/card_background/normal/',
            files: ['darkblue.png', 'darkgreen.png', 'lightblue.png', 'pink.png', 'red.png', 'tiffany.png', 'violet.png']
        },
        'Rare': {
            path: '../images/card_background/normal/',
            files: ['darkblue.png', 'darkgreen.png', 'lightblue.png', 'pink.png', 'red.png', 'tiffany.png', 'violet.png']
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
        const backgroundImage = await loadImage(getBackgroundByRarity(cardData.Rarity));
        ctx.drawImage(backgroundImage, 0, 0, width, height);

        const margin = width * 0.08;
        roundRect(ctx, margin, margin, width - margin * 2, height - margin * 2, 15);
        ctx.save();
        ctx.fillStyle = 'rgba(255, 255, 255, 0.98)';
        ctx.fill();
        ctx.restore();

        const pokemonImage = await loadImage(cardData.image_url);
        const pokeW = width * 0.68;
        const pokeH = height * 0.35;
        const pokeX = width * 0.16;
        const pokeY = width * 0.15;
        ctx.drawImage(pokemonImage, pokeX, pokeY, pokeW, pokeH);

        drawCardText(ctx, canvas, cardData, pokeX, pokeY, pokeW, pokeH);

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
        console.error('抽卡時發生錯誤:', error);
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
        console.error('加入卡冊時發生錯誤:', error);
        alert('發生錯誤，請稍後再試');
    });
}

// 添加 processAndDrawCards 函數
async function processAndDrawCards(cards) {
    const cardContainer = document.querySelector('.card-container');
    cardContainer.innerHTML = ''; // 清空現有卡片

    for (const card of cards) {
        // 創建卡片容器
        const cardItem = document.createElement('div');
        cardItem.className = 'card-item';
        
        // 置卡片HTML結構
        cardItem.innerHTML = `
            <div class="card-inner">
                <div class="card-back">
                    <img src="../images/card_back.png" alt="card back" width="250" height="350">
                </div>
                <div class="card-front">
                    <canvas class="card-canvas" width="250" height="350"></canvas>
                </div>
            </div>
        `;

        cardContainer.appendChild(cardItem);

        // 獲取canvas並繪製卡片
        const canvas = cardItem.querySelector('.card-canvas');
        try {
            const dataUrl = await drawCardDataURL(card, 250, 350);
            const img = new Image();
            img.onload = function() {
                const ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0);
            };
            img.src = dataUrl;
        } catch (error) {
            console.error('Error drawing card:', error);
        }

        // 添加翻牌效果
        cardItem.addEventListener('click', function() {
            this.querySelector('.card-inner').classList.add('card-flipped');
        });
    }

    // 顯示結果區域
    document.getElementById('cardResults').style.display = 'block';
}

function roundRect(ctx, x, y, width, height, radius) {
    ctx.beginPath();
    ctx.moveTo(x + radius, y);
    ctx.lineTo(x + width - radius, y);
    ctx.quadraticCurveTo(x + width, y, x + width, y + radius);
    ctx.lineTo(x + width, y + height - radius);
    ctx.quadraticCurveTo(x + width, y + height, x + width - radius, y + height);
    ctx.lineTo(x + radius, y + height);
    ctx.quadraticCurveTo(x, y + height, x, y + height - radius);
    ctx.lineTo(x, y + radius);
    ctx.quadraticCurveTo(x, y, x + radius, y);
    ctx.closePath();
}
