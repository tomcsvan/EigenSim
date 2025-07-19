cmake_minimum_required(VERSION 3.10)

# Project descriptor
include("cmake/options.cmake")
project(eigensim VERSION 0.1.0 LANGUAGES CXX)

# Project configuration
set(CMAKE_CXX_STANDARD 20)
set(CMAKE_CXX_STANDARD_REQUIRED True)
set(CMAKE_RUNTIME_OUTPUT_DIRECTORY "./bin")
set(CMAKE_CXX_FLAGS "${CMAKE_CXX_FLAGS} -g -O2 -Wall -Wextra -Wpedantic -Werror")

# Dependencies
include_directories("./include")
link_libraries(occi clntsh)

# Options
httplib_use_openssl()   # allow connection over HTTPS

# Sources
add_executable(eigensim ./src/eigensim.cpp)

# Tests
add_executable(test_libs ./tests/test_libs.cpp)
