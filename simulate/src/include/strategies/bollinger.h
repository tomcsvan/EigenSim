#pragma once

#include "strategy.h" 

namespace EigenSim::Strategies {

class Bollinger : public Strategy {  
public:
    Bollinger(size_t period = 20, double k = 2.0)
        : period(period), k(k) {}

    std::vector<TradePosition> trades(
        const std::vector<StockPrice>& history,
        const std::vector<StockPrice>& current,
        MoneyAmount capital) const override;

private:
    size_t period;
    double k;

    double sma(const std::vector<double>& data, size_t end, size_t len) const;
    double stddev(const std::vector<double>& data, size_t end, size_t len, double mean) const;
};

}
