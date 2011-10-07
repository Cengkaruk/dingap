
int Cmd = 0xFE ;  /* EZIO Command */
void Cls () { 
int cls = 1 ;    /* Clear screen */
  write(fd,&Cmd,1);
  write(fd,&cls,1);	
}

int init = 0x28 ; /* Initialize EZIO */ 
void Init () {
  write(fd,&Cmd,1);
  write(fd,&init,1);	
  write(fd,&Cmd,1);
  write(fd,&init,1);	
}

int stopsend = 0x37 ; /* Stop sending any data to EZIO */
void StopSend () {
  write(fd,&Cmd,1);
  write(fd,&init,1);	
}

int home = 0x2 ; /* Home cursor */
void Home () {
  write(fd,&Cmd,1);
  write(fd,&home,1);
}
	
int readkey = 0x6 ; /* Read key */
void ReadKey () {
  write(fd,&Cmd,1);
  write(fd,&readkey,1);
}

int blank = 0x8 ; /* Blank display */
void Blank () {
  write(fd,&Cmd,1);
  write(fd,&blank,1);
}

int hide = 0x0C ; /* Hide cursor & display blanked characters */
void Hide () {
  write(fd,&Cmd,1);
  write(fd,&hide,1);
}

int turn = 0x0D ; /* Turn On (blinking block cursor) */
void TurnOn () {
  write(fd,&Cmd,1);
  write(fd,&turn,1);
}

int show = 0x0E ; /* Show underline cursor */
void Show () {
  write(fd,&Cmd,1);
  write(fd,&show,1);
}

int movel = 0x10 ; /* Move cursor 1 character left */
void MoveL () {
  write(fd,&Cmd,1);
  write(fd,&movel,1);
}

int mover = 0x14 ; /* Move cursor 1 character right */
void MoveR () {
  write(fd,&Cmd,1);
  write(fd,&mover,1);
}

int scl = 0x18 ; /* Scroll cursor 1 character left */
void ScrollL(){
  write(fd,&Cmd,1);
  write(fd,&scl,1);
}

int scr = 0x1C ; /* Scroll cursor 1 character right */
void ScrollR(){
  write(fd,&Cmd,1);
  write(fd,&scr,1);
}

//int address = 0x84F; /* Set cursor address from 0x80 to 0x8F, 0x840 to 0x84F */
void SetAddress (int address) {
  write(fd,&Cmd,1);
  write(fd,&address,1);
}
