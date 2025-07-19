Server-side code for EigenSim

## Requirements

- A C++ compiler
- CMake


## Installation

### MacOS

I suggest first having [Homebrew](https://brew.sh/) as the package manager:

```sh
brew install llvm # C++ compiler suite
brew install cmake
```


## Running

Make sure that you are on the `simulate` folder, then:

```
cmake .
cmake --build .
```

Then head to the `bin` folder, and use the following executables:
- `eigensim`: the server executable (WIP)
- `test_libs`: tests the libraries
