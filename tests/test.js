jsonObject = {
  [':if']: 'test',
  ['[]']: [],
}

jsonObject = [
  {
    [':if']: 'test',
  },
  [
    'test'
  ]
]

jsonObject = {
  ':properties': {
    ':order': 0
  },
  html: [
    {
      ':properties': {
        order: 0
      }
    },
    {
      ':properties': {
        order: 'test'
      }
    }
  ]
}

abstractObject = [

]

console.log(jsonObject);