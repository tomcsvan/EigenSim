#include <strategies/momentum.h>

namespace EigenSim::Strategies {

std::vector<TradePosition> Momentum::trades(
    const std::vector<StockPrice>& history,
    const std::vector<StockPrice>& current,
    MoneyAmount capital) const {

    std::vector<TradePosition> trades;
    std::vector<StockPrice> data = history;
    data.insert(data.end(), current.begin(), current.end());

    if (data.size() < period + 2) return trades;

    bool in_position = false;

    for (size_t i = period + 1; i < data.size(); ++i) {
        double prev_momentum = data[i - 1].closing - data[i - 1 - period].closing;
        double curr_momentum = data[i].closing - data[i - period].closing;

        const auto& point = data[i];
        
        size_t quantity = static_cast<size_t>(capital * 0.05 / point.buy);
        if (!in_position && prev_momentum <= 0 && curr_momentum > 0) {
            trades.emplace_back(point.time, point.buy, quantity);
            in_position = true;
        } else if (in_position && prev_momentum >= 0 && curr_momentum < 0) {
            trades.back().close(point.time, point.sell);
            in_position = false;
        }
    }

    return trades;
}

}
