Server-side code for EigenSim

## Requirements

- A C++ compiler
- CMake


## Installation

### MacOS

I suggest first having [Homebrew](https://brew.sh/) as the package manager:

```sh
brew install llvm # C++ compiler suite
brew install cmake
```


## Running

Make sure that you are on the `simulate` folder, then:

```
cmake .
cmake --build .
```

Then head to the `bin` folder, and use the following executables:
- `eigensim`: the server executable (WIP)
- `test_libs`: tests the libraries



## API designs

### `GET /`

Retrieves the capabilities of the server. This includes:
- strategy IDs
- stock IDs and historical availability
- etc. (WIP)


### `POST /strategy`

Create a new strategy, composed of these parameters:
- WIP

Returns the newly created strategy's ID.

### `GET /backtest`

Invokes the backtest with the following parameters:
- stock ID
- strategy ID 
- start date
- end date

This will take a while, so some placeholder animation should be implemented on the client side.

Returns the JSON representation of a `Report` entity like this:

```json
{
    "report_id": "RPT20240601A",
    "backtest_id": "BT20240601",
    "generated_at": "2024-06-01T12:34:56Z",
    "total_return": 0.153,
    "annualized_return": 0.098,
    "sharpe_ratio": 1.42,
    "max_drawdown": -0.07,
    "win_rate": 0.62,
    "trade_count": 120,
    "t_stat": 2.15,
    "p_value": 0.032,
    "confidence_95_low": 0.085,
    "confidence_95_high": 0.121
}
```

