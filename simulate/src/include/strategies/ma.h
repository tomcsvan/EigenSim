#pragma once

#include "strategy.h"

namespace EigenSim::Strategies {

class MovingAverageCrossover : public Strategy {
public:
    std::vector<TradePosition> trades(
        const std::vector<StockPrice>& history,
        const std::vector<StockPrice>& current,
        MoneyAmount capital) const override;
};

}