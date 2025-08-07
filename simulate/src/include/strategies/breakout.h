#pragma once

#include "strategy.h"
namespace EigenSim::Strategies {

class Breakout : public Strategy {
public:
    Breakout(size_t period = 20)
        : period(period) {}

    std::vector<TradePosition> trades(
        const std::vector<StockPrice>& history,
        const std::vector<StockPrice>& current,
        MoneyAmount capital) const override;
private:
    size_t period;
};

}
