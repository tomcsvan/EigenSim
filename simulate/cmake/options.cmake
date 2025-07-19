# Provide some additional compiling options.

function(httplib_use_openssl)
    add_definitions(-DCPPHTTPLIB_OPENSSL_SUPPORT)
    link_libraries(ssl crypto)
endfunction()