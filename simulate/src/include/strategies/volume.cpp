#include <strategies/volume.h>
#include <numeric>

namespace EigenSim::Strategies {

std::vector<TradePosition> Volume::trades(
    const std::vector<StockPrice>& history,
    const std::vector<StockPrice>& current,
    MoneyAmount capital) const {

    std::vector<TradePosition> trades;
    std::vector<StockPrice> data = history;
    data.insert(data.end(), current.begin(), current.end());

    if (data.size() < period + 2) return trades;

    bool in_position = false;

    for (size_t i = period + 1; i < data.size(); ++i) {
        double avg_volume = 0;
        for (size_t j = i - period; j < i; ++j) {
            avg_volume += data[j].volume;
        }
        avg_volume /= period;

        const auto& prev = data[i - 1];
        const auto& point = data[i];

        bool price_up = point.closing > prev.closing;
        bool price_down = point.closing < prev.closing;
        bool high_volume = point.volume > volume_multiplier * avg_volume;
        
        size_t quantity = static_cast<size_t>(capital * 0.05 / point.buy);
        if (!in_position && price_up && high_volume) {
            trades.emplace_back(point.time, point.buy, quantity);
            in_position = true;
        } else if (in_position && price_down && high_volume) {
            trades.back().close(point.time, point.sell);
            in_position = false;
        }
    }

    return trades;
}

}
