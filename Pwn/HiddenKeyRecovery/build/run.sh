#!/bin/bash

socat TCP-LISTEN:1337,fork exec:"/chall/run.sh",pty,echo=0,stderr &
sleep 3
socat TCP-LISTEN:1338,fork exec:"./dma.py",pty,echo=0,stderr 
