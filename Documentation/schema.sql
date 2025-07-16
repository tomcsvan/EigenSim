CREATE TABLE Users ( 
    user_id CHAR(12) PRIMARY KEY,
    first_name VARCHAR2(20),
    last_name VARCHAR2(20),
    password_hash CHAR(60)
);

CREATE TABLE Strategy ( 
    strategy_id CHAR(12) PRIMARY KEY,
    name VARCHAR2(50),
    description CLOB,
    created_date DATE
);
CREATE TABLE CustomStrategy ( 
    strategy_id CHAR(12) PRIMARY KEY,
    custom_prompt CLOB,
    FOREIGN KEY (strategy_id) REFERENCES Strategy(strategy_id) ON DELETE CASCADE
);

CREATE TABLE PredefinedStrategy ( 
    strategy_id CHAR(12) PRIMARY KEY,
    logic CLOB,
    FOREIGN KEY (strategy_id) REFERENCES Strategy(strategy_id) ON DELETE CASCADE
);
CREATE TABLE Parameter ( 
    parameter_id CHAR(12) PRIMARY KEY,
    name VARCHAR2(30) UNIQUE,
    type VARCHAR2(10) CHECK (type IN ('int', 'float', 'bool', 'string'))
);
CREATE TABLE ParameterValue (
    strategy_id CHAR(12),
    parameter_id CHAR(12),
    assigned_value VARCHAR2(100), 
    PRIMARY KEY(strategy_id, parameter_id),
    FOREIGN KEY(strategy_id) REFERENCES Strategy(strategy_id) ON DELETE CASCADE,
    FOREIGN KEY(parameter_id) REFERENCES Parameter(parameter_id) ON DELETE CASC>
);
CREATE TABLE Ticker (
    ticker_symbol CHAR(8) PRIMARY KEY,
    company_name VARCHAR2(100),
    exchange VARCHAR2(20)
);
CREATE TABLE Backtest (
    backtest_id CHAR(12) PRIMARY KEY,
    strategy_id CHAR(12),
    user_id CHAR(12),
    ticker_symbol CHAR(8),
    start_date DATE,
    end_date DATE,
    FOREIGN KEY(user_id) REFERENCES User(user_id),
    FOREIGN KEY(strategy_id) REFERENCES Strategy(strategy_id),
    FOREIGN KEY(ticker_symbol) REFERENCES Ticker(ticker_symbol)
);
CREATE TABLE Report (
    report_id CHAR(20) PRIMARY KEY,
    backtest_id CHAR(12) UNIQUE,
    generated_at TIMESTAMP,
    total_return FLOAT,
    annualized_return FLOAT,
    sharpe_ratio FLOAT,
    max_drawdown FLOAT,
    win_rate FLOAT,
    trade_count NUMBER,
    t_stat FLOAT,
    p_value FLOAT,
    confidence_95_low FLOAT,
    confidence_95_high FLOAT,
    FOREIGN KEY(backtest_id) REFERENCES Backtest(backtest_id) ON DELETE CASCADE
);

CREATE TABLE Trade (
    trade_id CHAR(12) PRIMARY KEY,
    backtest_id CHAR(12),
    trade_time TIMESTAMP,
    price FLOAT,
    quantity NUMBER,
    side VARCHAR2(4) CHECK (side IN ('BUY', 'SELL')),
    FOREIGN KEY(backtest_id) REFERENCES Backtest(backtest_id) ON DELETE CASCADE
);