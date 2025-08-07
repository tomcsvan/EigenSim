#pragma once

#include "strategy.h" 

namespace EigenSim::Strategies {

class Momentum : public Strategy { 
public:
    Momentum(size_t period = 10)
        : period(period) {}

    std::vector<TradePosition> trades(
        const std::vector<StockPrice>& history,
        const std::vector<StockPrice>& current,
        MoneyAmount capital) const override;  

private:
    size_t period;
};

}
