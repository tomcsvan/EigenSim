/**
 * @file src/stock.h
 * @brief Defines basic structures for stock trading.
 */

#ifndef STOCK_H
#define STOCK_H

#include <chrono>
#include <iostream>
#include <string>
#include <vector>

namespace EigenSim {

typedef double MoneyAmount;

struct StockPrice {
    std::string time;
    double opening;
    double closing;
    double buy;
    double sell;
    double volume;

    StockPrice(std::string t = "NA",
               double open = 0.0,
               double close = 0.0,
               double buy = 0.0,
               double sell = 0.0,
               double vol = 0.0)
        : time(t), opening(open), closing(close), buy(buy), sell(sell), volume(vol) {}

    friend std::ostream& operator<<(std::ostream& os, const StockPrice& sp) {
        os << "Time: " << sp.time
           << ", Opening: " << sp.opening
           << ", Closing: " << sp.closing
           << ", Buy: " << sp.buy
           << ", Sell: " << sp.sell
           << ", Volume: " << sp.volume;
        return os;
    }
};

// Enforce pairing of buy/sell
struct TradePosition {
    std::string entry_time;
    double entry_price;
    size_t quantity;

    std::string exit_time = "";
    double exit_price = 0.0;
    bool closed = false;

    TradePosition(const std::string& entry_time,
                  double entry_price,
                  size_t quantity)
        : entry_time(entry_time),
          entry_price(entry_price),
          quantity(quantity) {}

    void close(const std::string& exit_time, double exit_price) {
        this->exit_time = exit_time;
        this->exit_price = exit_price;
        this->closed = true;
    }

    [[nodiscard]] double profit() const {
        return closed ? (exit_price - entry_price) * quantity : 0.0;
    }
};

}  // namespace EigenSim

#endif  // STOCK_H
