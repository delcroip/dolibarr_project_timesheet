# https://hub.docker.com/r/sphinxdoc/sphinx

Usage

Create a Sphinx project::

$ docker run -it --rm -v ./:/docs sphinxdoc/sphinx sphinx-quickstart

Build HTML document::

$ docker run --rm -v ./:/docs sphinxdoc/sphinx make html

Build EPUB document::

$ docker run --rm -v ./:/docs sphinxdoc/sphinx make epub

Build PDF document::

$ docker run --rm -v ./:/docs sphinxdoc/sphinx-latexpdf make latexpdf