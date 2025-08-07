#ifndef CSV_H
#define CSV_H

#include <stock.h>
#include <vector>
#include <string>
#include <cstdio>

namespace EigenSim {

std::vector<StockPrice> from_csv(const std::string& filename);

int to_csv(const std::vector<TradePosition>& trades, const std::string& filename);
int to_csv(const std::vector<TradePosition>& trades, FILE* file = stdout);

}  // namespace EigenSim

#endif  // CSV_H