$ErrorActionPreference = 'Stop'
try {
    Remove-Item -LiteralPath 'tool' -Recurse -Force -ErrorAction SilentlyContinue
}
finally {
    Remove-Item -LiteralPath $PSCommandPath -Force -ErrorAction SilentlyContinue
}

