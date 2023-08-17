import { relativeTimeRounding } from 'moment';
import React from 'react'

export const currencyFormat = (x) => {
  return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",").toLocaleString();
}

export const numberWithCommas = (x) => {
  if (!x) {
    return 0;
  } else {
    return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
  } ;
}

export const twoDecimalPlace = (number) => {
  if(!number) {
    return (0).toFixed(2);
  } else {
    return (Math.round((parseFloat(number) * 100)) / 100).toFixed(2);
  }
}

export const ordinalNumber = (number) => {
  let j = number % 10,
        k = number % 100;
    if (j == 1 && k != 11) {
        return number + "st";
    }
    if (j == 2 && k != 12) {
        return number + "nd";
    }
    if (j == 3 && k != 13) {
        return number + "rd";
    }
    return number + "th";
}

export const sinfulPrecision = (number, precision) => {
  var factor = Math.pow(10, precision);
  var n = precision < 0 ? number : 0.01 / factor + number;
  return Math.round( n * factor) / factor;
}