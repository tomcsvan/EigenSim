CREATE TABLE Users ( 
    user_id VARCHAR2(12) PRIMARY KEY,
    first_name VARCHAR2(20),
    last_name VARCHAR2(20),
    password_hash VARCHAR2(100)
);

CREATE TABLE CustomStrategy ( 
    strategy_id VARCHAR2(12) PRIMARY KEY,
    name VARCHAR2(50),
    description CLOB,
    created_date DATE
    custom_prompt CLOB
);

CREATE TABLE PredefinedStrategy ( 
    strategy_id VARCHAR2(12) PRIMARY KEY,
    name VARCHAR2(50),
    description CLOB,
    created_date DATE
    logic CLOB
);

CREATE TABLE Parameter ( 
    parameter_id VARCHAR2(12) PRIMARY KEY,
    name VARCHAR2(30) UNIQUE,
    type VARCHAR2(10) CHECK (type IN ('int', 'float', 'bool', 'string'))
);

CREATE TABLE ParameterValue (
    strategy_id VARCHAR2(12),
    parameter_id VARCHAR2(12),
    assigned_value VARCHAR2(100), 
    PRIMARY KEY(strategy_id, parameter_id),
    FOREIGN KEY(strategy_id) REFERENCES Strategy(strategy_id) ON DELETE CASCADE,
    FOREIGN KEY(parameter_id) REFERENCES Parameter(parameter_id) ON DELETE CASCADE
);

CREATE TABLE Ticker (
    ticker_symbol VARCHAR2(10) PRIMARY KEY,
    company_name VARCHAR2(100),
    exchange VARCHAR2(20)
);

CREATE TABLE Backtest (
    backtest_id VARCHAR2(12) PRIMARY KEY,
    strategy_id VARCHAR2(12),
    user_id VARCHAR2(12),
    ticker_symbol VARCHAR2(10),
    start_date DATE,
    end_date DATE,
    FOREIGN KEY(user_id) REFERENCES Users(user_id),
    FOREIGN KEY(strategy_id) REFERENCES Strategy(strategy_id),
    FOREIGN KEY(ticker_symbol) REFERENCES Ticker(ticker_symbol)
);

CREATE TABLE Report (
    report_id VARCHAR2(20) PRIMARY KEY,
    backtest_id VARCHAR2(12) UNIQUE,
    generated_at TIMESTAMP,
    total_return NUMBER,
    annualized_return NUMBER,
    sharpe_ratio NUMBER,
    max_drawdown NUMBER,
    win_rate NUMBER,
    trade_count NUMBER,
    t_stat NUMBER,
    p_value NUMBER,
    confidence_95_low NUMBER,
    confidence_95_high NUMBER,
    FOREIGN KEY(backtest_id) REFERENCES Backtest(backtest_id) ON DELETE CASCADE
);

CREATE TABLE Trade (
    trade_id VARCHAR2(12) PRIMARY KEY,
    backtest_id VARCHAR2(12),
    trade_time TIMESTAMP,
    price NUMBER,
    quantity NUMBER,
    side VARCHAR2(4) CHECK (side IN ('BUY', 'SELL')),
    FOREIGN KEY(backtest_id) REFERENCES Backtest(backtest_id) ON DELETE CASCADE
);
