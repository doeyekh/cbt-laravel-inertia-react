function formatPercentage(value) {
    return value === null || value === undefined ? '-' : `${Number(value).toFixed(1)}%`;
}