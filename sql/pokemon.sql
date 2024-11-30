USE Pokemon;

CREATE TABLE df_pokemon (
    ID INT,               
    Name VARCHAR(100) NOT NULL PRIMARY KEY,           
    Species VARCHAR(100) DEFAULT NULL,         
    Variant VARCHAR(50) DEFAULT NULL,           
    Generation INT,                    
    Rarity VARCHAR(50) DEFAULT NULL,           
    Evolves_from VARCHAR(100) DEFAULT NULL,    
    Has_gender_diff BOOLEAN DEFAULT FALSE,     
    Type1 VARCHAR(50) NOT NULL,                 
    Type2 VARCHAR(50) DEFAULT NULL,             
    Total INT DEFAULT NULL,                     
    HP INT DEFAULT NULL,                       
    Attack INT DEFAULT NULL,                    
    Defense INT DEFAULT NULL,                   
    Sp_Atk INT DEFAULT NULL,                    
    Sp_Def INT DEFAULT NULL,                    
    Speed INT DEFAULT NULL,                     
    image_url TEXT DEFAULT NULL,                
    VGC2022_rules VARCHAR(100) DEFAULT NULL,    
    Monthly_Usage_k FLOAT DEFAULT NULL,         
    Usage_Percent FLOAT DEFAULT NULL,         
    Monthly_Rank INT DEFAULT NULL            
);

