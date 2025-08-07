#include <strategies/mean.h>

namespace EigenSim::Strategies {

std::vector<TradePosition> Mean::trades(
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
        double sum = 0;
        for (size_t j = i - period; j < i; ++j)
            sum += data[j].closing;

        double sma = sum / period;
        double price = data[i].closing;
        double deviation = (price - sma) / sma;

        const auto& point = data[i];

        size_t quantity = static_cast<size_t>(capital * 0.05 / point.buy);
        if (!in_position && deviation < -threshold) {
            trades.emplace_back(point.time, point.buy, quantity);
            in_position = true;
        } else if (in_position && deviation > threshold) {
            trades.back().close(point.time, point.sell);
            in_position = false;
        }
    }

    return trades;
}

}
