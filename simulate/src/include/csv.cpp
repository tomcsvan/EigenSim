#include <fmt/core.h>
#include <fstream>
#include <sstream>
#include <vector>

#include "csv.h"
#include <stock.h>

namespace EigenSim {

std::vector<StockPrice> from_csv(const std::string& filename) {
    std::vector<StockPrice> prices;
    std::ifstream file(filename);

    if (!file.is_open()) {
        fmt::print(stderr, "Error opening file: {}\n", filename);
        return prices;
    }

    std::string line;
    std::getline(file, line);  // skip header

    while (std::getline(file, line)) {
        std::stringstream ss(line);
        std::string timestamp, open, high, low, close, volume;

        if (!std::getline(ss, timestamp, ',') ||
            !std::getline(ss, open, ',') ||
            !std::getline(ss, high, ',') ||
            !std::getline(ss, low, ',') ||
            !std::getline(ss, close, ',') ||
            !std::getline(ss, volume, ',')) {
            fmt::print(stderr, "Skipping malformed line: {}\n", line);
            continue;
        }

        try {
            prices.emplace_back(
                timestamp,
                std::stod(open),
                std::stod(close),
                std::stod(open),  
                std::stod(close),   
                std::stod(volume)
            );
        } catch (const std::exception& e) {
            fmt::print(stderr, "Failed to parse line: {} ({})\n", line, e.what());
            continue;
        }
    }

    return prices;
}

// Write trades + holdings
int to_csv(const std::vector<TradePosition>& trades, FILE* file) {
    fmt::print(file, "time,action,price,quantity\n");

    for (const auto& trade : trades) {
        if (trade.closed) {
            fmt::print(file, "{},buy,{:.2f},{}\n", trade.entry_time, trade.entry_price, trade.quantity);
            fmt::print(file, "{},sell,{:.2f},{}\n", trade.exit_time, trade.exit_price, trade.quantity);
        }
    }

    double total_cost = 0;
    size_t total_qty = 0;

    for (const auto& trade : trades) {
        if (!trade.closed) {
            total_cost += trade.entry_price * trade.quantity;
            total_qty += trade.quantity;
        }
    }

    if (total_qty > 0) {
        double avg_price = total_cost / total_qty;
        fmt::print(file, "NA,holdings,{:.2f},{}\n", avg_price, total_qty);
    }

    return 0;
}

int to_csv(const std::vector<TradePosition>& trades, const std::string& filename) {
    FILE* file = fopen(filename.c_str(), "w");
    if (!file)
        throw std::runtime_error("Could not open file for writing: " + filename);
    to_csv(trades, file);
    fclose(file);
    return 0;
}

}  // namespace EigenSim
