document.addEventListener("DOMContentLoaded", () => {
    const USER_ID = document.querySelector('meta[name="user-id"]')?.getAttribute('content');
    if (!USER_ID) {
        console.error("USER_ID 未定义，无法继续执行抽卡逻辑！");
        return;
    }

    document.querySelectorAll('.pack-button').forEach(button => {
        button.addEventListener('click', function (e) {
            e.preventDefault(); // 防止默认提交行为

            // 扣除金币请求
            fetch('deduct_coins.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ cost: 10 })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // 扣款成功，更新显示的金币数
                        updateCoinDisplay(data.new_balance);

                        // 调用抽卡逻辑
                        fetch('pakage.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: 'action=draw_card'
                        })
                            .then(response => response.json())
                            .then(data => {
                                if (data.cards && data.cards.length > 0) {
                                    processAndDrawCards(data.cards);
                                }
                            })
                            .catch(error => {
                                console.error('抽卡时发生错误:', error);
                                alert('发生错误，请稍后再试');
                            });
                    } else {
                        // 扣款失败，检查原因
                        if (data.message === "餘額不足") {
                            alert("餘額不足，無法抽卡。");
                        } else {
                            alert("扣款失敗：" + (data.message || "未知錯誤"));
                        }
                    }
                })
                .catch(error => {
                    console.error('扣除皮卡幣时发生错误:', error);
                });
        });
    });

    function updateCoinDisplay(newBalance) {
        const coinDisplay = document.getElementById('coin-amount');
        if (coinDisplay) {
            coinDisplay.textContent = newBalance;
        }
    }
});
