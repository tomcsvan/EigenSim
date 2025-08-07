#pragma once

#include "strategy.h"

namespace EigenSim::Strategies {

class Mean : public Strategy {
public:
    Mean(size_t period = 20, double threshold = 0.02)
        : period(period), threshold(threshold) {}

    std::vector<TradePosition> trades(
        const std::vector<StockPrice>& history,
        const std::vector<StockPrice>& current,
        MoneyAmount capital) const override;

private:
    size_t period;
    double threshold;
};

}
