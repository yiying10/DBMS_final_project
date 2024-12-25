function getBackgroundByRarity(rarity) {
    // 定義每個稀有度對應的背景圖片目錄和可用的背景圖片名稱
    const backgroundFiles = {
        'Common': {
            path: '../images/card_background/normal/',
            files: [
                'darkblue.png',
                'darkgreen.png',
                'lightblue.png',
                'pink.png',
                'red.png',
                'tiffany.png',
                'violet.png'
            ]
        },
        'Rare': {
            path: '../images/card_background/rare/',
            files: [
                'blackblue.png',
                'blue.png',
                'bluepurple.png',
                'colorful.png',
                'metalpurple.png',
                'pink.png',
                'razer.png',
                'red.png',
                'silver.png',
                'whiteblue.png'
            ]
        }
    };

    // 獲取對應稀有度的配置
    const config = backgroundFiles[rarity] || backgroundFiles['Common'];
    
    // 從可用文件列表中隨機選擇一個
    const randomFile = config.files[Math.floor(Math.random() * config.files.length)];
    
    // 返回完整的圖片路徑
    return config.path + randomFile;
}
