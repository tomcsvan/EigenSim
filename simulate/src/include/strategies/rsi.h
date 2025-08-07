#pragma once

#include "strategy.h" 

namespace EigenSim::Strategies {

class RSI : public Strategy { 
public:
    RSI(double buy_threshold = 30.0,
        double sell_threshold = 70.0,
        size_t period = 14)
        : buy_threshold(buy_threshold),
          sell_threshold(sell_threshold),
          period(period) {}

    std::vector<TradePosition> trades(
        const std::vector<StockPrice>& history,
        const std::vector<StockPrice>& current,
        MoneyAmount capital) const override; 

private:
    double buy_threshold;
    double sell_threshold;
    size_t period;

    std::tuple<double, double, double> initialize_rsi(
        const std::vector<StockPrice>& window) const;
};

}
