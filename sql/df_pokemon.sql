USE Pokemon;
CREATE TABLE df_pokemon (
    ID INT NOT NULL PRIMARY KEY,                -- 每個寶可夢唯一的編號
    Name VARCHAR(100) NOT NULL,                 -- 寶可夢的名稱
    Species VARCHAR(100) DEFAULT NULL,          -- 物種名稱
    Variant VARCHAR(50) DEFAULT NULL,           -- 變種名稱，例如 "Mega" 或 "Gigantamax"
    Generation INT NOT NULL,                    -- 第幾世代
    Rarity VARCHAR(50) DEFAULT NULL,            -- 稀有度
    Evolves_from VARCHAR(100) DEFAULT NULL,     -- 進化自的寶可夢
    Has_gender_diff BOOLEAN DEFAULT FALSE,      -- 是否有性別差異
    Type1 VARCHAR(50) NOT NULL,                 -- 第一類型
    Type2 VARCHAR(50) DEFAULT NULL,             -- 第二類型
    Total INT DEFAULT NULL,                     -- 總能力值
    HP INT DEFAULT NULL,                        -- 血量
    Attack INT DEFAULT NULL,                    -- 攻擊力
    Defense INT DEFAULT NULL,                   -- 防禦力
    Sp_Atk INT DEFAULT NULL,                    -- 特殊攻擊力
    Sp_Def INT DEFAULT NULL,                    -- 特殊防禦力
    Speed INT DEFAULT NULL,                     -- 速度
    image_url TEXT DEFAULT NULL,                -- 圖片連結
    VGC2022_rules VARCHAR(100) DEFAULT NULL,    -- 比賽規則相關描述
    Monthly_Usage_k FLOAT DEFAULT NULL,         -- 月使用數據（千次）
    Usage_Percent FLOAT DEFAULT NULL,           -- 使用率百分比
    Monthly_Rank INT DEFAULT NULL               -- 每月排名
);

