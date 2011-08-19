#!/bin/bash

KAVSDK_PATH=/opt/KAV_SDK8_L3-Linux-x86_gcc345-glibc232_8.1.3.109-Release-Lic
#KAVSDK_PATH=/home/darryl/source/kaspersky/KAV_SDK8_L3-Linux-x86_gcc345-glibc232_8.1.3.107-Release-Lic
#KAVSDK_PATH=/home/darryl/source/kaspersky/KAV_SDK8_L3-Linux-x86_gcc345-glibc232_8.0.3.75-Release-Lic
KL_PLUGINS_PATH=$KAVSDK_PATH/ppl
LD_LIBRARY_PATH=$KL_PLUGINS_PATH:$KAVSDK_PATH/lib:$LD_LIBRARY_PATH

export KAVSDK_PATH LD_LIBRARY_PATH KL_PLUGINS_PATH
