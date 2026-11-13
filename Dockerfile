# syntax=docker/dockerfile:1.5-labs

FROM php:8.4-cli-trixie as spc-composer

RUN apt-get update && \
    apt-get install --quiet --assume-yes --no-install-recommends \
    git \
    libtool \
    bison \
    flex \
    automake \
    autopoint \
    patchelf \
    unzip \
    cmake \
    nasm \
    procps \
    libzip-dev && \
    rm --recursive --force /var/lib/apt/lists/*

RUN docker-php-ext-install -j "$(nproc)" zip

COPY --from=composer/composer:latest /usr/bin/composer /usr/local/bin/composer

ADD --keep-git-dir=true https://github.com/crazywhalecc/static-php-cli.git#2.8.5 /spc

RUN chmod +x /spc/bin/spc

ENV PATH="$PATH:/spc/bin"

WORKDIR /spc

RUN composer install --no-dev --classmap-authoritative --no-progress --quiet

WORKDIR /spc_cache

RUN spc doctor --auto-fix



FROM php:8.4-cli-trixie

RUN apt-get update && \
    apt-get install --quiet --assume-yes --no-install-recommends \
    git \
    libtool \
    bison \
    flex \
    automake \
    autopoint \
    patchelf \
    musl-tools \
    unzip \
    cmake \
    nasm && \
    rm --recursive --force /var/lib/apt/lists/*

# Don't provide extra configuration, this fails the sanity check when PHP isn't built to include these.
RUN rm -r /usr/local/etc/php/conf.d/*

COPY --from=spc-composer /spc /spc
COPY --from=spc-composer /usr/local/musl /usr/local/musl

ENV PATH="$PATH:/spc/bin"

RUN git config --global --add safe.directory /spc && \
    git config --global --add safe.directory /sparc && \
    git config --global --add safe.directory /sparc/arcanist

RUN mkdir --parents --mode=777 /spc_cache/source && \
    mkdir --parents --mode=777 /spc_cache/pkgroot && \
    mkdir --parents --mode=777 /spc_cache/buildroot && \
    mkdir --parents --mode=777 /spc_cache/downloads && \
    mkdir --parents --mode=777 /spc_cache/dist && \
    mkdir --parents --mode=777 /spc_cache/log

WORKDIR /spc_cache

ENTRYPOINT [ "/sparc/build-sparc.php" ]
CMD [ "--overwrite" ]
