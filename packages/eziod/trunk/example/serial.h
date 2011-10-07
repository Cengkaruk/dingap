#include        <stdlib.h>
#include        <termios.h>
#include        <unistd.h>
#include        <fcntl.h>
#include        <errno.h>
#include        <ctype.h>
#include	<stdio.h>

#define FALSE   0
#define TRUE    1

// serial device name
const char *serial_port_names[]=
	{"ttyS0", "ttyS1", "ttyS2", "ttyS3", "ttyS4",
	"ttyS5", "ttyS6", "ttyS7", "ttyS8", "ttyS9"};

// com port handle
int fd;

//------------------------------------------------------------------------------
int Serial_Init(int port, int baud_rate)
{
	char devname[512];
	int brate;
        struct termios term; 

	//make device name string
	sprintf(devname, "/dev/%s", serial_port_names[port]);
	
	//open device
	fd = open(devname, 2);
	if (fd <= 0)
	{
		//error opening port
		printf("unable to open device\n");
		return(1);
	}

	//get baud rate
	switch (baud_rate)
	{
		case 1200:
			brate=B1200;
			break;
		case 1800:
			brate=B1800;
			break;
		case 2400:
			brate=B2400;
			break;
		case 4800:
			brate=B4800;
			break;
		case 9600:
			brate=B9600;
			break;
		case 19200:
			brate=B19200;
			break;
		case 38400:
			brate=B38400;
			break;
		default:
			printf("invalid baud rate: %d\n", baud_rate);
			return(2);
	}

	//get device struct
        if (tcgetattr(fd, &term) != 0)
        {
                printf("tcgetattr failed\n");
                return(3);
        }

        //input modes 
        term.c_iflag &= ~(IGNBRK|BRKINT|PARMRK|INPCK|ISTRIP|INLCR|IGNCR|ICRNL
                        |IUCLC|IXON|IXANY|IXOFF|IMAXBEL);
        term.c_iflag |= IGNPAR;

        //output modes 
        term.c_oflag &= ~(OPOST|OLCUC|ONLCR|OCRNL|ONOCR|ONLRET|OFILL
                        |OFDEL|NLDLY|CRDLY|TABDLY|BSDLY|VTDLY|FFDLY);
        term.c_oflag |= NL0|CR0|TAB0|BS0|VT0|FF0;

        //control modes
        term.c_cflag &= ~(CSIZE|PARENB|CRTSCTS|PARODD|HUPCL);
        term.c_cflag |= CREAD|CS8|CSTOPB|CLOCAL;

        //local modes 
        term.c_lflag &= ~(ISIG|ICANON|IEXTEN|ECHO|FLUSHO|PENDIN);
        term.c_lflag |= NOFLSH;

        //set baud rate
        cfsetospeed(&term, brate);
        cfsetispeed(&term, brate);

	//set new device settings
        if (tcsetattr(fd, TCSANOW, &term)  != 0)
        {
		printf("tcsetattr failed\n");
		return(4);
        }
}

void Uninit_Serial()
{
	close(fd);
	fd=(int)NULL;
}
