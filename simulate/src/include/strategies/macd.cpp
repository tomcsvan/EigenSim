#include <strategies/macd.h>
#include <stdexcept>

namespace EigenSim::Strategies {

std::vector<double> MACD::ema(const std::vector<double>& prices, size_t period) const {
    std::vector<double> result(prices.size(), -1);
    if (prices.size() < period) return result;

    double sum = 0;
    for (size_t i = 0; i < period; ++i) sum += prices[i];
    result[period - 1] = sum / period;

    double multiplier = 2.0 / (period + 1);

    for (size_t i = period; i < prices.size(); ++i) {
        result[i] = (prices[i] - result[i - 1]) * multiplier + result[i - 1];
    }

    return result;
}

std::vector<TradePosition> MACD::trades(
    const std::vector<StockPrice>& history,
    const std::vector<StockPrice>& current,
    MoneyAmount capital) const {

    std::vector<TradePosition> trades;

    std::vector<StockPrice> data = history;
    data.insert(data.end(), current.begin(), current.end());

    if (data.size() < long_period + signal_period)
        return trades;

    std::vector<double> closes;
    for (const auto& p : data) closes.push_back(p.closing);

    auto ema_short = ema(closes, short_period);
    auto ema_long = ema(closes, long_period);

    std::vector<double> macd_line(data.size(), -1);
    for (size_t i = 0; i < data.size(); ++i) {
        if (ema_short[i] >= 0 && ema_long[i] >= 0) {
            macd_line[i] = ema_short[i] - ema_long[i];
        }
    }

    auto signal_line = ema(macd_line, signal_period);

    bool in_position = false;

    for (size_t i = 1; i < data.size(); ++i) {
        if (macd_line[i - 1] < 0 || signal_line[i - 1] < 0 ||
            macd_line[i] < 0 || signal_line[i] < 0)
            continue;

        double prev_diff = macd_line[i - 1] - signal_line[i - 1];
        double curr_diff = macd_line[i] - signal_line[i];

        const auto& point = data[i];

        size_t quantity = static_cast<size_t>(capital * 0.05 / point.buy);
        if (!in_position && prev_diff <= 0 && curr_diff > 0) {
            trades.emplace_back(point.time, point.buy, quantity);
            in_position = true;
        } else if (in_position && prev_diff >= 0 && curr_diff < 0) {
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
