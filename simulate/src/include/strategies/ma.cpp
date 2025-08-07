#include <strategies/ma.h>

namespace EigenSim::Strategies {

std::vector<TradePosition> MovingAverageCrossover::trades(
    const std::vector<StockPrice>& history,
    const std::vector<StockPrice>& current,
    MoneyAmount capital
) const {
    const int short_period = 10;
    const int long_period = 50;

    std::vector<TradePosition> trades;
    bool in_position = false;

    auto compute_sma = [](const std::vector<StockPrice>& data, int index, int period) -> double {
        if (index + 1 < period) return -1;
        double sum = 0;
        for (int i = index - period + 1; i <= index; ++i) {
            sum += data[i].closing;
        }
        return sum / period;
    };

    for (size_t i = 1; i < history.size(); ++i) {
        double short_ma_prev = compute_sma(history, i - 1, short_period);
        double long_ma_prev  = compute_sma(history, i - 1, long_period);
        double short_ma      = compute_sma(history, i, short_period);
        double long_ma       = compute_sma(history, i, long_period);

        if (short_ma < 0 || long_ma < 0 || short_ma_prev < 0 || long_ma_prev < 0) {
            continue; // Not enough data
        }

        const auto& point = history[i];

        size_t quantity = static_cast<size_t>(capital * 0.05 / point.buy);
        if (!in_position && short_ma_prev <= long_ma_prev && short_ma > long_ma) {
            trades.emplace_back(point.time, point.closing, quantity);
            in_position = true;
        } else if (in_position && short_ma_prev >= long_ma_prev && short_ma < long_ma) {
            trades.back().close(point.time, point.closing);
            in_position = false;
        }
    }

    if (in_position) {
        trades.pop_back();
    }

    return trades;
}

}
