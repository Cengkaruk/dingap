/* Setting icon */
int icon1[] = { 64,0x02,65,0x06,66,0x0F,67,0x1F,
               68,0x0F,69,0x06,70,0x02,71,0x00 };
int icon2[] = { 72,0x02,73,0x06,74,0x0F,75,0x1F,
               76,0x0F,77,0x06,78,0x02,79,0x00 };
int icon3[] = { 80,0x02,81,0x06,82,0x0F,83,0x1F,
               84,0x0F,85,0x06,86,0x02,87,0x00 };
int icon4[] = { 88,0x02,89,0x06,90,0x0F,91,0x1F,
               92,0x0F,93,0x06,94,0x02,95,0x00 };
int icon5[] = { 96,0x02,97,0x06,98,0x0F,99,0x1F,
               100,0x0F,101,0x06,102,0x02,103,0x00 };
int icon6[] = { 104,0x02,105,0x06,106,0x0F,107,0x1F,
               108,0x0F,109,0x06,110,0x02,111,0x00 };
int icon7[] = { 112,0x02,113,0x06,114,0x0F,115,0x1F,
               116,0x0F,117,0x06,118,0x02,119,0x00 };
int icon8[] = { 120,0x02,121,0x06,122,0x0F,123,0x1F,
               124,0x0F,125,0x06,126,0x02,127,0x00 };
    
/* Init Icon Function */
void init_icon1() {
  
  write(fd,&Cmd,1);
  write(fd,&icon1[0],1);
  write(fd,&icon1[1],1);
  write(fd,&Cmd,1);
  write(fd,&icon1[2],1);
  write(fd,&icon1[3],1);
  write(fd,&Cmd,1);
  write(fd,&icon1[4],1);
  write(fd,&icon1[5],1);
  write(fd,&Cmd,1);
  write(fd,&icon1[6],1);
  write(fd,&icon1[7],1);
  write(fd,&Cmd,1);
  write(fd,&icon1[8],1);
  write(fd,&icon1[9],1);
  write(fd,&Cmd,1);
  write(fd,&icon1[10],1);
  write(fd,&icon1[11],1);
  write(fd,&Cmd,1);
  write(fd,&icon1[12],1);
  write(fd,&icon1[13],1);
  write(fd,&Cmd,1);
  write(fd,&icon1[14],1);
  write(fd,&icon1[15],1);
}

void init_icon2() {
  
  write(fd,&Cmd,1);
  write(fd,&icon2[0],1);
  write(fd,&icon2[1],1);
  write(fd,&Cmd,1);
  write(fd,&icon2[2],1);
  write(fd,&icon2[3],1);
  write(fd,&Cmd,1);
  write(fd,&icon2[4],1);
  write(fd,&icon2[5],1);
  write(fd,&Cmd,1);
  write(fd,&icon2[6],1);
  write(fd,&icon2[7],1);
  write(fd,&Cmd,1);
  write(fd,&icon2[8],1);
  write(fd,&icon2[9],1);
  write(fd,&Cmd,1);
  write(fd,&icon2[10],1);
  write(fd,&icon2[11],1);
  write(fd,&Cmd,1);
  write(fd,&icon2[12],1);
  write(fd,&icon2[13],1);
  write(fd,&Cmd,1);
  write(fd,&icon2[14],1);
  write(fd,&icon2[15],1);
}


void init_icon3() {
  
  write(fd,&Cmd,1);
  write(fd,&icon3[0],1);
  write(fd,&icon3[1],1);
  write(fd,&Cmd,1);
  write(fd,&icon3[2],1);
  write(fd,&icon3[3],1);
  write(fd,&Cmd,1);
  write(fd,&icon3[4],1);
  write(fd,&icon3[5],1);
  write(fd,&Cmd,1);
  write(fd,&icon3[6],1);
  write(fd,&icon3[7],1);
  write(fd,&Cmd,1);
  write(fd,&icon3[8],1);
  write(fd,&icon3[9],1);
  write(fd,&Cmd,1);
  write(fd,&icon3[10],1);
  write(fd,&icon3[11],1);
  write(fd,&Cmd,1);
  write(fd,&icon3[12],1);
  write(fd,&icon3[13],1);
  write(fd,&Cmd,1);
  write(fd,&icon3[14],1);
  write(fd,&icon3[15],1);
}


void init_icon4() {
  
  write(fd,&Cmd,1);
  write(fd,&icon4[0],1);
  write(fd,&icon4[1],1);
  write(fd,&Cmd,1);
  write(fd,&icon4[2],1);
  write(fd,&icon4[3],1);
  write(fd,&Cmd,1);
  write(fd,&icon4[4],1);
  write(fd,&icon4[5],1);
  write(fd,&Cmd,1);
  write(fd,&icon4[6],1);
  write(fd,&icon4[7],1);
  write(fd,&Cmd,1);
  write(fd,&icon4[8],1);
  write(fd,&icon4[9],1);
  write(fd,&Cmd,1);
  write(fd,&icon4[10],1);
  write(fd,&icon4[11],1);
  write(fd,&Cmd,1);
  write(fd,&icon4[12],1);
  write(fd,&icon4[13],1);
  write(fd,&Cmd,1);
  write(fd,&icon4[14],1);
  write(fd,&icon4[15],1);
}


void init_icon5() {
  
  write(fd,&Cmd,1);
  write(fd,&icon5[0],1);
  write(fd,&icon5[1],1);
  write(fd,&Cmd,1);
  write(fd,&icon5[2],1);
  write(fd,&icon5[3],1);
  write(fd,&Cmd,1);
  write(fd,&icon5[4],1);
  write(fd,&icon5[5],1);
  write(fd,&Cmd,1);
  write(fd,&icon5[6],1);
  write(fd,&icon5[7],1);
  write(fd,&Cmd,1);
  write(fd,&icon5[8],1);
  write(fd,&icon5[9],1);
  write(fd,&Cmd,1);
  write(fd,&icon5[10],1);
  write(fd,&icon5[11],1);
  write(fd,&Cmd,1);
  write(fd,&icon5[12],1);
  write(fd,&icon5[13],1);
  write(fd,&Cmd,1);
  write(fd,&icon5[14],1);
  write(fd,&icon5[15],1);
}


void init_icon6() {
  
  write(fd,&Cmd,1);
  write(fd,&icon6[0],1);
  write(fd,&icon6[1],1);
  write(fd,&Cmd,1);
  write(fd,&icon6[2],1);
  write(fd,&icon6[3],1);
  write(fd,&Cmd,1);
  write(fd,&icon6[4],1);
  write(fd,&icon6[5],1);
  write(fd,&Cmd,1);
  write(fd,&icon6[6],1);
  write(fd,&icon6[7],1);
  write(fd,&Cmd,1);
  write(fd,&icon6[8],1);
  write(fd,&icon6[9],1);
  write(fd,&Cmd,1);
  write(fd,&icon6[10],1);
  write(fd,&icon6[11],1);
  write(fd,&Cmd,1);
  write(fd,&icon6[12],1);
  write(fd,&icon6[13],1);
  write(fd,&Cmd,1);
  write(fd,&icon6[14],1);
  write(fd,&icon6[15],1);
}


void init_icon7() {
  
  write(fd,&Cmd,1);
  write(fd,&icon7[0],1);
  write(fd,&icon7[1],1);
  write(fd,&Cmd,1);
  write(fd,&icon7[2],1);
  write(fd,&icon7[3],1);
  write(fd,&Cmd,1);
  write(fd,&icon7[4],1);
  write(fd,&icon7[5],1);
  write(fd,&Cmd,1);
  write(fd,&icon7[6],1);
  write(fd,&icon7[7],1);
  write(fd,&Cmd,1);
  write(fd,&icon7[8],1);
  write(fd,&icon7[9],1);
  write(fd,&Cmd,1);
  write(fd,&icon7[10],1);
  write(fd,&icon7[11],1);
  write(fd,&Cmd,1);
  write(fd,&icon7[12],1);
  write(fd,&icon7[13],1);
  write(fd,&Cmd,1);
  write(fd,&icon7[14],1);
  write(fd,&icon7[15],1);
}


void init_icon8() {
  
  write(fd,&Cmd,1);
  write(fd,&icon8[0],1);
  write(fd,&icon8[1],1);
  write(fd,&Cmd,1);
  write(fd,&icon8[2],1);
  write(fd,&icon8[3],1);
  write(fd,&Cmd,1);
  write(fd,&icon8[4],1);
  write(fd,&icon8[5],1);
  write(fd,&Cmd,1);
  write(fd,&icon8[6],1);
  write(fd,&icon8[7],1);
  write(fd,&Cmd,1);
  write(fd,&icon8[8],1);
  write(fd,&icon8[9],1);
  write(fd,&Cmd,1);
  write(fd,&icon8[10],1);
  write(fd,&icon8[11],1);
  write(fd,&Cmd,1);
  write(fd,&icon8[12],1);
  write(fd,&icon8[13],1);
  write(fd,&Cmd,1);
  write(fd,&icon8[14],1);
  write(fd,&icon8[15],1);
}

/* Initial all icon */
init_all_icon () {
  init_icon1();
  init_icon2();
  init_icon3();
  init_icon4();
  init_icon5();
  init_icon6();
  init_icon7();
  init_icon8();
  //Cls();
}

/* Call icon i from 0 to 7 */
void show_icon(int i){

  write(fd,&i,1);
}
