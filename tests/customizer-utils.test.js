function parsePersonalizationData(json) {
  try {
    return JSON.parse(json);
  } catch {
    return null;
  }
}

test('parsePersonalizationData returns object for valid JSON', () => {
  const json = '{"text_0":"Hello","dropdown_1":"Option 1"}';
  expect(parsePersonalizationData(json)).toEqual({ text_0: 'Hello', dropdown_1: 'Option 1' });
});

test('parsePersonalizationData returns null for invalid JSON', () => {
  expect(parsePersonalizationData('not json')).toBeNull();
}); 