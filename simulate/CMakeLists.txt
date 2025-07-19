cmake_minimum_required(VERSION 3.10)

# Project descriptor
project(eigensim)

# Vars
set(CMAKE_CXX_STANDARD 20)
set(CMAKE_RUNTIME_OUTPUT_DIRECTORY ${CMAKE_SOURCE_DIR}/bin)
set(CMAKE_CXX_FLAGS "${CMAKE_CXX_FLAGS} -g -Wall -Wextra -Wpedantic -Werror")

# Options declaration
function(httplib_openssl_on)
    add_definitions(-DCPPHTTPLIB_OPENSSL_SUPPORT)
    link_libraries(ssl crypto)
endfunction()

# Options
httplib_openssl_on()

# Dependencies
include_directories(${CMAKE_SOURCE_DIR}/include)


message(STATUS "Build eigensim")

# Sources
add_executable(eigensim ./src/eigensim.cpp)
add_executable(test_libs ./tests/test_libs.cpp)





