#include <strategies/rsi.h>
#include <stdexcept>

namespace EigenSim::Strategies {

std::tuple<double, double, double> RSI::initialize_rsi(
    const std::vector<StockPrice>& window) const {
    if (window.size() < period + 1) {
        throw std::invalid_argument("Not enough data to calculate RSI.");
    }

    double gain = 0.0, loss = 0.0;
    for (size_t i = 1; i <= period; ++i) {
        double delta = window[i].closing - window[i - 1].closing;
        if (delta > 0) gain += delta;
        else loss -= delta;
    }

    double avg_gain = gain / period;
    double avg_loss = loss / period;

    double rs = avg_loss == 0 ? 0 : avg_gain / avg_loss;
    double rsi = avg_loss == 0 ? 100.0 : 100.0 - (100.0 / (1.0 + rs));

    return {avg_gain, avg_loss, rsi};
}

std::vector<TradePosition> RSI::trades(
    const std::vector<StockPrice>& history,
    const std::vector<StockPrice>& current,
    MoneyAmount capital) const {

    std::vector<TradePosition> trades;

    // Join history + current
    std::vector<StockPrice> data = history;
    data.insert(data.end(), current.begin(), current.end());

    if (data.size() < period + 2)
        return trades;

    double avg_gain, avg_loss, rsi_val;
    std::tie(avg_gain, avg_loss, rsi_val) =
        initialize_rsi({data.begin(), data.begin() + period + 1});

    bool in_position = false;

    for (size_t i = period + 1; i < data.size(); ++i) {
        double delta = data[i].closing - data[i - 1].closing;
        double gain = delta > 0 ? delta : 0.0;
        double loss = delta < 0 ? -delta : 0.0;

        avg_gain = (avg_gain * (period - 1) + gain) / period;
        avg_loss = (avg_loss * (period - 1) + loss) / period;

        double rs = avg_loss == 0 ? 0 : avg_gain / avg_loss;
        double rsi = avg_loss == 0 ? 100.0 : 100.0 - (100.0 / (1.0 + rs));

        const auto& point = data[i];
        size_t quantity = static_cast<size_t>(capital * 0.05 / point.buy);
        if (!in_position && rsi < buy_threshold) {
            trades.emplace_back(point.time, point.buy, quantity);
            in_position = true;
        } else if (in_position && rsi > sell_threshold) {
            trades.back().close(point.time, point.sell);
            in_position = false;
        }
    }

    return trades;
}

}
