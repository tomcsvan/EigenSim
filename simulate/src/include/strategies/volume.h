#pragma once

#include "strategy.h" 

namespace EigenSim::Strategies {

class Volume : public Strategy { 
public:
    Volume(size_t period = 20, double volume_multiplier = 1.5)
        : period(period), volume_multiplier(volume_multiplier) {}

    std::vector<TradePosition> trades(
        const std::vector<StockPrice>& history,
        const std::vector<StockPrice>& current,
        MoneyAmount capital) const override;  

private:
    size_t period;
    double volume_multiplier;
};

}
