#ifndef STRATEGIES_BASE_H
#define STRATEGIES_BASE_H

#include <stock.h>
#include <vector>

namespace EigenSim {

namespace Strategies {

class Strategy {
   public:
    virtual ~Strategy() = default;

    virtual std::vector<TradePosition> trades(
        const std::vector<StockPrice>& history,
        const std::vector<StockPrice>& current_data,
        MoneyAmount starting_amount) const = 0;
};

}  // namespace Strategies

}  // namespace EigenSim

#endif  // STRATEGIES_BASE_H
