# EigenSim

EigenSim is a modular algorithmic trading backtesting platform designed for performance, flexibility, and precision. It allows users to define, execute, and evaluate trading strategies using historical market data in a structured and scalable environment.

Built with PHP, Oracle SQL, and C++, the system supports both predefined strategies and natural language-driven custom strategies. The platform is designed to evolve toward supporting machine learning workflows for predictive modeling and strategy generation.

---

## Key Features

- User authentication and session control  
- Historical market data integration and management  
- Support for predefined logic-based strategies  
- Custom strategies defined via natural language prompts  
- C++ simulation engines for strategy execution and report generation  
- Performance analytics: return, Sharpe ratio, drawdown, confidence intervals  
- Oracle database schema with normalized relational structure  
- (Planned) Machine learning integration for signal generation and optimization  

---

## Technology Stack

| Layer         | Technology                       |
|---------------|----------------------------------|
| Interface     | PHP, HTML, CSS                   |
| Backend Logic | C++ (compiled binaries)          |
| Data Storage  | Oracle SQL                       |
| Data Source   | Polygon.io API (price history)   |

---

## System Architecture

```text
/public_html
├── assets/                # Shared styling and logos
├── common/                # Shared PHP components (DB, auth, layout)
├── account/               # Account dashboard and strategy interface
├── run_engine.php         # Orchestrates simulation pipeline
├── dashboard.php          # User landing page
/simulate
├── engine_predef          # C++ binary for predefined strategy execution
├── engine_custom          # C++ binary for custom natural-language strategies
├── report_engine          # C++ binary to analyze trades and generate reports
```

---

## Database Design

Core entities include:

- `Users`: Registered platform users  
- `Strategy`: General metadata for strategies  
- `CustomStrategy`, `PredefinedStrategy`: Separated strategy types  
- `Parameter`, `ParameterValue`: Tunable inputs for strategies  
- `Backtest`: Simulation runs with metadata  
- `Trade`: Trade-level simulation output  
- `ReportStats`: Summary statistics for each backtest  

---

## Example Use Case
A user logs in, selects AAPL stock data from Jan–Jun 2024, and launches a backtest with a custom mean-reversion strategy. The system:

1. Writes the prompt and parameters to a temporary file  
2. Invokes the appropriate C++ engine  
3. Stores the resulting trades and metrics in the Oracle database  
4. Displays a detailed performance report in the browser
---

## Future Development

- Live trading via broker APIs  
- Strategy optimization (e.g., grid search, genetic algorithms)  
- Interactive visualizations (PnL curve, drawdown chart, trade markers)  
- **Machine learning module** to:
  - Predict directional signals (classification)
  - Forecast returns or volatility (regression)
  - Perform feature engineering on technical indicators
  - Evaluate model-based strategies vs rule-based baselines  

---
## Authors

Tom Le, Quan Nguyen, Ziyan He  
University of British Columbia - 2025
