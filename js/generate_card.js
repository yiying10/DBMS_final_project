function generateCard(imageUrl, name, rarity, type1, type2) {
    const canvas = document.getElementById("cardCanvas");
    const ctx = canvas.getContext("2d");

    // 顯示 Canvas
    canvas.style.display = "block";

    // 清空畫布
    ctx.clearRect(0, 0, canvas.width, canvas.height);

    // 繪製卡片背景
    ctx.fillStyle = "#f8f8f8";
    ctx.fillRect(0, 0, canvas.width, canvas.height);
    ctx.strokeStyle = "#000";
    ctx.lineWidth = 4;
    ctx.strokeRect(10, 10, canvas.width - 20, canvas.height - 20);

    // 檢查是否有圖片URL
    if (!imageUrl) {
        // 如果沒有圖片，顯示提示文字
        ctx.font = "20px Arial";
        ctx.fillStyle = "#666";
        ctx.textAlign = "center";
        ctx.fillText("圖片不存在", canvas.width / 2, 200);
        
        // 繼續繪製其他信息
        drawCardInfo();
        return;
    }

    // 繪製寶可夢圖片
    const img = new Image();
    
    img.onerror = () => {
        console.error('圖片載入失敗');
        ctx.font = "20px Arial";
        ctx.fillStyle = "#666";
        ctx.textAlign = "center";
        ctx.fillText("圖片載入失敗", canvas.width / 2, 200);
        drawCardInfo();
    };
    
    img.onload = () => {
        ctx.drawImage(img, 150, 100, 200, 200);
        drawCardInfo();
    };

    img.src = imageUrl;

    // 將繪製文字信息的代碼抽取為函數
    function drawCardInfo() {
        // 繪製卡片名稱
        ctx.font = "bold 24px Arial";
        ctx.fillStyle = "#000";
        ctx.textAlign = "center";
        ctx.fillText(name, canvas.width / 2, 50);

        // 繪製稀有度
        ctx.font = "20px Arial";
        ctx.fillText(`稀有度: ${rarity}`, canvas.width / 2, 350);

        // 繪製屬性
        ctx.fillText(`屬性: ${type1} ${type2 ? "/ " + type2 : ""}`, canvas.width / 2, 400);

        // 顯示下載按鈕
        document.getElementById("downloadButton").style.display = "inline-block";
    }
}

function downloadCard() {
    const canvas = document.getElementById("cardCanvas");
    
    // 先檢查 canvas 是否存在
    if (!canvas) {
        console.error('找不到 canvas 元素');
        alert('下載失敗：找不到畫布元素');
        return;
    }

    // 檢查 canvas 是否有內容
    try {
        const context = canvas.getContext('2d');
        const imageData = context.getImageData(0, 0, canvas.width, canvas.height);
        const isEmpty = imageData.data.every(pixel => pixel === 0);
        
        if (isEmpty) {
            console.error('Canvas 是空的');
            alert('請先生成卡片再下載');
            return;
        }

        // 嘗試下載
        canvas.toBlob(function(blob) {
            if (!blob) {
                console.error('無法創建 blob');
                alert('下載失敗：無法創建圖片數據');
                return;
            }

            try {
                const url = window.URL.createObjectURL(blob);
                const link = document.createElement("a");
                link.download = "pokemon_card.png";
                link.href = url;
                link.click();
                
                // 延遲清理，確保下載開始
                setTimeout(() => {
                    window.URL.revokeObjectURL(url);
                }, 100);
            } catch (error) {
                console.error('創建下載連結失敗:', error);
                alert('下載失敗：' + error.message);
            }
        }, 'image/png', 1.0);

    } catch (error) {
        console.error('下載過程發生錯誤:', error);
        alert('下載失敗：' + error.message);
    }
}
