#include <strategies/bollinger.h>
#include <cmath>
#include <stdexcept>

namespace EigenSim::Strategies {

double Bollinger::sma(const std::vector<double>& data, size_t end, size_t len) const {
    if (end + 1 < len) return -1;
    double sum = 0;
    for (size_t i = end - len + 1; i <= end; ++i) {
        sum += data[i];
    }
    return sum / len;
}

double Bollinger::stddev(const std::vector<double>& data, size_t end, size_t len, double mean) const {
    if (end + 1 < len) return -1;
    double sum_sq = 0;
    for (size_t i = end - len + 1; i <= end; ++i) {
        double diff = data[i] - mean;
        sum_sq += diff * diff;
    }
    return std::sqrt(sum_sq / len);
}

std::vector<TradePosition> Bollinger::trades(
    const std::vector<StockPrice>& history,
    const std::vector<StockPrice>& current,
    MoneyAmount capital) const {

    std::vector<TradePosition> trades;

    std::vector<StockPrice> data = history;
    data.insert(data.end(), current.begin(), current.end());

    if (data.size() < period + 1) return trades;

    std::vector<double> closes;
    for (const auto& pt : data) closes.push_back(pt.closing);

    bool in_position = false;

    for (size_t i = period; i < data.size(); ++i) {
        double mean = sma(closes, i, period);
        double deviation = stddev(closes, i, period, mean);
        double upper_band = mean + k * deviation;
        double lower_band = mean - k * deviation;

        const auto& point = data[i];

        size_t quantity = static_cast<size_t>(capital * 0.05 / point.buy);
        if (!in_position && point.closing < lower_band) {
            trades.emplace_back(point.time, point.buy, quantity);
            in_position = true;
        } else if (in_position && point.closing > upper_band) {
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
