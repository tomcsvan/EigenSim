#include <iostream>
#include <unordered_map>
#include <vector>
#include <memory>

#include <strategies/strategy.h>
#include <csv.h>
#include <stock.h>
#include <strategies/bollinger.h>
#include <strategies/breakout.h>
#include <strategies/ma.h>
#include <strategies/macd.h>
#include <strategies/mean.h>
#include <strategies/momentum.h>
#include <strategies/rsi.h>
#include <strategies/volume.h>

using namespace EigenSim;
using namespace EigenSim::Strategies;

// Map strategy_id to factory functions

std::unique_ptr<Strategy> get_strategy(const std::string& strategy_id) {
    if (strategy_id == "S1000000001") {
        return std::make_unique<MovingAverageCrossover>();
    } else if (strategy_id == "S1000000002") {
        return std::make_unique<RSI>();
    } else if (strategy_id == "S1000000003") {
        return std::make_unique<MACD>();
    } else if (strategy_id == "S1000000004") {
        return std::make_unique<Bollinger>();
    } else if (strategy_id == "S1000000005") {
        return std::make_unique<Breakout>();
    } else if (strategy_id == "S1000000006") {
        return std::make_unique<Mean>();
    } else if (strategy_id == "S1000000007") {
        return std::make_unique<Volume>();
    } else if (strategy_id == "S1000000008") {
    return std::make_unique<Momentum>();
    } else {
        throw std::invalid_argument("Unknown strategy ID: " + strategy_id);
    }
}

int main(int argc, char* argv[]) {
    if (argc < 5) {
        std::cerr
            << "Usage: " << argv[0]
            << " <history_csv> <current_csv> <initial_capital> <strategy_id>\n";
        return 1;
    }

    std::string history_path = argv[1];
    std::string current_path = argv[2];
    MoneyAmount capital      = std::stod(argv[3]);
    std::string strategy_id  = argv[4];

    auto history = from_csv(history_path);
    auto current = from_csv(current_path);

    if (history.empty() || current.empty()) {
        std::cerr << "History or current data is empty.\n";
        return 1;
    }


    std::unique_ptr<Strategy> strategy = get_strategy(strategy_id);

    auto trades = strategy->trades(history, current, capital);
    to_csv(trades);
    
    return 0;
}
