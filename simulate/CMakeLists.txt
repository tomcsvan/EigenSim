cmake_minimum_required(VERSION 3.10)

# Project descriptor
project(eigensim)

# Vars
set(CMAKE_CXX_STANDARD 20)
set(CMAKE_SOURCE_DIR ./src)
set(CMAKE_BINARY_DIR ./bin)

message(STATUS "Build eigensim")


add_executable(eigensim ./src/eigensim.cpp)
add_executable(test_eigensim ./src/eigensim.cpp)