import React from 'react'

import { createContext } from 'react'
import { createContextualCan, useAbility } from '@casl/react'

import LoadSpinner from 'common/Loading'

export const AbilityContext = createContext();
export const Can = createContextualCan(AbilityContext.Consumer);

export const can = (I, a) => {
  const ability = useAbility(AbilityContext);

  return ability.can(I, a) ? 'd-block' : 'd-none';
}

export const CanWithLoader = ({ isLoading, ...rest }) => {
  return (
      isLoading ? <LoadSpinner  /> : <Can {...rest}>{rest.children}</Can>
  );
}