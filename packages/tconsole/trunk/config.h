#ifndef _CONFIG_H
#define _CONFIG_H

#if defined(_VENDOR_CLEAROS)
#define PRODUCT_NAME			"ClearOS"
#elif defined(_VENDOR_DIRECTPOINTE)
#define PRODUCT_NAME			"CentralPointe"
#elif defined(_VENDOR_O2I)
#define PRODUCT_NAME			"SERV OBox 2"
#elif defined(_VENDOR_MSONA)
#define PRODUCT_NAME			"Msona"
#elif defined(_VENDOR_COMMGATE)
#define PRODUCT_NAME			"CommGate"
#else
#define PRODUCT_NAME			"ClarkConnect"
#endif

#endif // _CONFIG_H

// vi: ts=4

