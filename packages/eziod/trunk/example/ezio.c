#include "serial.h"
#include "command.h"
#include "icon.h"
#include "string.h"

#define VERSION "1.2"

//com port. 0=COM1 or ttyS0
#define COMPORT 1 
//baud rate
#define BAUD 2400



/* Add or Change Show Message here */
char mes1[] = "*Portwell EZIO *";
char mes2[] = "****************";
char mes3[] = "Up is selected";
char mes4[] = "Down is selected";
char mes5[] = "Enter is selected";
char mes6[] = "ESC is selected";
char nul[] = "                                       ";

int a,b;
void ShowMessage (char *str1 , char *str2) {
   a = strlen(str1); // 16
   b = 40 - a;		 // 40 - 16
   write(fd,str1,a);
   write(fd,nul,b);  // 24 spaces
   write(fd,str2,strlen(str2));
}

int main(int argc, char* argv[]) {

   fprintf(stderr,"Portwell Inc. EZIO command-line communications sample " VERSION"\n"); 
   Serial_Init(COMPORT, BAUD);  /* Initialize RS-232 environment */ 
   Init(); 			/* Initialize EZIO */
   Cls();			/* Clear screen */
   init_all_icon();		/* Initialize all icon */
   Cls();			/* Clear screen */
//   ShowMessage(mes1,mes2);
 //  SetAddress(0x4f);
	show_icon(1);
   Show();
   TurnOn();

   while (1) {			
     int res;
     unsigned char buf[255];
     
     ReadKey(); /* sub-routine to send "read key" command */
     res = read(fd,buf,255); /* read response from EZIO */
     
     switch(buf[1]) {        /* Switch the Read command */
     
	    case 0xbe : 	/* Up Botton was received */
		   Cls();
	           ShowMessage(mes1,mes3); /** display "Portwell EZIO" */
	           break;                  /** display "Up is selected */
	 

        case 0xbd : 	/** Down Botton was received */
	           Cls();
	           ShowMessage(mes1,mes4);  /** display "Portwell EZIO" */
	           break;                   /** display "Down is selected" */
	 
        case 0xbb :	/** Enter Botton was received */
	           Cls();
	           ShowMessage(mes1,mes5);  /** display "Portwell EZIO" */
	           break;                   /** display "Enter is selected" */
	     
        case 0xb7 :	/** Escape Botton was received */
	           Cls();
	           ShowMessage(mes1,mes6);  /** display "Portwell EZIO" */
	           break;                   /** display "Escape is selected */
       } 
 
  }

  printf("Done.\n\n");
  Uninit_Serial();
  return 0;
 
}
