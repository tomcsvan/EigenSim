#include <strategies/breakout.h>
#include <algorithm>

namespace EigenSim::Strategies {

std::vector<TradePosition> Breakout::trades(
    const std::vector<StockPrice>& history,
    const std::vector<StockPrice>& current,
    MoneyAmount capital) const {

    std::vector<TradePosition> trades;

    std::vector<StockPrice> data = history;
    data.insert(data.end(), current.begin(), current.end());

    if (data.size() < period + 1)
        return trades;

    bool in_position = false;

    for (size_t i = period; i < data.size(); ++i) {
        double highest_close = data[i - period].closing;
        double lowest_close = data[i - period].closing;

        for (size_t j = i - period + 1; j < i; ++j) {
            highest_close = std::max(highest_close, data[j].closing);
            lowest_close = std::min(lowest_close, data[j].closing);
        }

        const auto& point = data[i];

        size_t quantity = static_cast<size_t>(capital * 0.05 / point.buy);
        if (!in_position && point.closing > highest_close) {
            trades.emplace_back(point.time, point.buy, quantity);
            in_position = true;
        } else if (in_position && point.closing < lowest_close) {
            trades.back().close(point.time, point.sell);
            in_position = false;
        }
    }

    if (in_position) {
        trades.pop_back();
    }

    return trades;
}

}
