let currentPokemonData = {}; // 這裡將在 PHP 中設置

// 確保 pokemonData 是全局可用的
console.log('Pokemon Data:', currentPokemonData); // 添加調試信息

let selectedBackground = ''; // 新增變數來存儲選擇的背景

function switchBackgroundType(type) {
    document.querySelectorAll('.background-type-selector button').forEach(btn => {
        btn.classList.remove('active');
    });
    document.querySelector(`button[onclick="switchBackgroundType('${type}')"]`).classList.add('active');

    document.getElementById('normal-backgrounds').style.display = type === 'normal' ? 'grid' : 'none';
    document.getElementById('rare-backgrounds').style.display = type === 'rare' ? 'grid' : 'none';
}

function selectBackground(bgPath) {
    selectedBackground = bgPath; // 儲存選擇的背景

    document.querySelectorAll('.background-option').forEach(opt => {
        opt.classList.remove('selected');
    });

    const selectedBg = document.querySelector(`.background-option[onclick*="${bgPath}"]`);
    if (selectedBg) {
        selectedBg.classList.add('selected');
    }

    generateCard(currentPokemonData, '../images/card_background/' + bgPath);
}

function generateCard(pokemonData, backgroundName) {
    const canvas = document.getElementById('cardCanvas');
    const ctx = canvas.getContext('2d');
    console.log('Generating card with background:', backgroundName);

    // 處理寶可夢名稱：
    // displayName 保持原始格式（大寫開頭，空格）用於顯示
    // imageFileName 轉小寫且空格換成-，用於圖片路徑
    const displayName = pokemonData.name; // 保持原始名稱
    const imageFileName = pokemonData.name.toLowerCase().replace(/\s+/g, '-'); // 轉小寫且空格換成-
    const pokemonImagePath = `../images/pokemon_images/${imageFileName}.png`;
    console.log('Pokemon image path:', pokemonImagePath);

    const bgImg = new Image();
    bgImg.crossOrigin = "Anonymous";
    bgImg.onerror = function () {
        console.error('Background image failed to load:', backgroundName);
    };
    bgImg.onload = function () {
        ctx.clearRect(0, 0, canvas.width, canvas.height);

        // 背景
        ctx.save();
        roundRect(ctx, 0, 0, canvas.width, canvas.height, 25);
        ctx.clip();
        ctx.drawImage(bgImg, 0, 0, canvas.width, canvas.height);
        ctx.restore();

        // 白色底圖
        ctx.save();
        roundRect(ctx, 30, 30, canvas.width - 60, canvas.height - 60, 15);
        ctx.fillStyle = 'rgba(255, 255, 255, 0.98)';
        ctx.fill();
        ctx.restore();

        // 檢查圖片是否存在和稀有度是否存在
        fetch(pokemonImagePath)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Image not found');
                }
                if (!pokemonData.rarity) {
                    throw new Error('No rarity defined');
                }
                return true;
            })
            .then(() => {
                // 圖片存在且有稀有度，繪製完整卡片
                const pokemonImg = new Image();
                pokemonImg.crossOrigin = "Anonymous";
                pokemonImg.onload = function () {
                    const imgWidth = canvas.width - 160;
                    const imgHeight = (canvas.height - 140) * 0.5;
                    ctx.drawImage(pokemonImg, 80, 90, imgWidth, imgHeight);
                    drawCardText(imgHeight, displayName);
                };
                pokemonImg.src = pokemonImagePath;
            })
            .catch((error) => {
                // 圖片不存在或無稀有度，不繪製任何內容
                console.log('Skipping card generation:', error.message);
            });
    };
    bgImg.src = backgroundName;

    function drawCardText(imgHeight, displayName) {
        // 文字排版
        ctx.textAlign = 'left';
        let y = imgHeight + 120;

        // 標題（使用原始名稱，保持大寫開頭）
        ctx.fillStyle = '#333';
        ctx.font = 'bold 28px "Cinzel", "Noto Sans TC", serif';
        ctx.fillText(displayName, 90, y);

        // 分隔線
        y += 20;
        ctx.beginPath();
        ctx.strokeStyle = '#ddd';
        ctx.lineWidth = 1;
        ctx.moveTo(90, y);
        ctx.lineTo(canvas.width - 90, y);
        ctx.stroke();

        // 稀有度
        y += 35;
        ctx.font = '22px "Cinzel", "Noto Sans TC", serif';
        ctx.fillStyle = '#333';
        ctx.fillText('稀有度', 90, y);
        ctx.font = 'bold 22px "Cinzel", "Noto Sans TC", serif';
        ctx.fillText(pokemonData.rarity, 180, y);

        // 屬性（如果有）
        if (pokemonData.type1) {
            y += 35;
            ctx.font = '22px "Cinzel", "Noto Sans TC", serif';
            ctx.fillText('屬性', 90, y);
            ctx.font = 'bold 22px "Cinzel", "Noto Sans TC", serif';
            const types = pokemonData.type2
                ? `${pokemonData.type1} / ${pokemonData.type2}`
                : pokemonData.type1;
            ctx.fillText(types, 180, y);
        }

        // 特性和描述（如果有）
        if (pokemonData.ability && pokemonData.ability.name) {
            y += 35;
            ctx.font = '22px "Cinzel", "Noto Sans TC", serif';
            ctx.fillText('特性', 90, y);
            ctx.font = 'bold 22px "Cinzel", "Noto Sans TC", serif';
            ctx.fillText(pokemonData.ability.name, 180, y);

            if (pokemonData.ability.description) {
                y += 35;
                ctx.font = '18px "Cinzel", "Noto Sans TC", serif';
                const maxWidth = canvas.width - 180;
                wrapText(ctx, pokemonData.ability.description, 90, y, maxWidth, 25);
            }
        }
    }
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

function downloadCard() {
    try {
        const canvas = document.getElementById('cardCanvas');
        const selectedBg = document.querySelector('.background-option.selected');
        const bgPath = selectedBg.getAttribute('onclick').match(/'([^']+)'/)[1];
        const bgName = bgPath.split('/').pop().split('.')[0];

        // 將寶可夢名稱中的空格替換為底線
        const safePokemonName = currentPokemonData.name.replace(/\s+/g, '_');
        const fileName = `${safePokemonName}_${bgName}.png`;

        const dataURL = canvas.toDataURL('image/png', 1.0);

        const link = document.createElement('a');
        link.download = fileName;
        link.href = dataURL;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    } catch (error) {
        console.error('下載過程發生錯誤:', error);
        alert('下載失敗: ' + error.message);
    }
}

function addToBooklet() {
    const pokemonData = JSON.parse(localStorage.getItem('selected_pokemon')); // 从 localStorage 获取当前宝可梦数据
    fetch('../php/booklet_add.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify([{
            name: pokemonData.name,
            rarity: pokemonData.rarity,
            type1: pokemonData.type1,
            type2: pokemonData.type2,
            image_url: pokemonData.image_url // 确保有这个字段
        }])
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('卡片已成功加入卡冊！');
            } else {
                alert('加入卡冊失敗: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('發生錯誤，請稍後再試。');
        });
}

// 確保在頁面加載時自動選擇第一個背景
window.onload = function () {
    const firstBg = document.querySelector('.background-option');
    if (firstBg) {
        const bgPath = firstBg.getAttribute('onclick').match(/'([^']+)'/)[1];
        selectBackground(bgPath); // 儲存第一個背景
    }
};

// 在 script 標籤內添加文字換行函數
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