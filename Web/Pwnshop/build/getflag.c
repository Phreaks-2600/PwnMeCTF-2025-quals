#include <stdio.h>
#include <stdlib.h>
#include <string.h>

int main(int argc, char *argv[]) {
    if (argc != 2 || strcmp(argv[1], "PWNME") != 0) {
        fprintf(stderr, "Usage : ./getflag PWNME\n");
        return EXIT_FAILURE;
    }

    FILE *file;
    char ch;

    file = fopen("/root/flag.txt", "r");
    if (file == NULL) {
        perror("Cannot read the flag, open a ticket on the discord.");
        return EXIT_FAILURE;
    }

    while ((ch = fgetc(file)) != EOF) {
        putchar(ch);
    }

    fclose(file);
    return EXIT_SUCCESS;
}