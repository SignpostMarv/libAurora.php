DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
cd ${DIR}
mkdir -p ../docs/
cd ../
rm -fr docs/*
doxygen utils/doxygen.conf