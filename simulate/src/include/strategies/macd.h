#pragma once

#include "strategy.h"

namespace EigenSim::Strategies {

class MACD : public Strategy {
public:
    MACD(size_t short_period = 12,
         size_t long_period = 26,
         size_t signal_period = 9)
        : short_period(short_period),
          long_period(long_period),
          signal_period(signal_period) {}

    std::vector<TradePosition> trades(
        const std::vector<StockPrice>& history,
        const std::vector<StockPrice>& current,
        MoneyAmount capital) const override;

private:
    size_t short_period;
    size_t long_period;
    size_t signal_period;

    std::vector<double> ema(const std::vector<double>& prices, size_t period) const;
};

}
