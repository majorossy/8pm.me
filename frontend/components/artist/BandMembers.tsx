'use client';

import { BandMember } from '@/lib/types';
import { useTheme } from '@/context/ThemeContext';

interface BandMembersProps {
  members?: BandMember[];
  formerMembers?: BandMember[];
}

export default function BandMembers({ members, formerMembers }: BandMembersProps) {
  const { theme } = useTheme();

  // Return null if no data to display
  if (!members?.length && !formerMembers?.length) {
    return null;
  }

  const isTron = theme === 'tron';
  const isMetro = theme === 'metro';
  const isJamify = theme === 'jamify';

  // Jamify/Spotify style
  if (isJamify) {
    return (
      <section className="px-4 md:px-8 pb-8">
        <h2 className="text-xl md:text-2xl font-bold text-white mb-6">Band Members</h2>

        <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
          {/* Current Members */}
          {members && members.length > 0 && (
            <div>
              <h3 className="text-base md:text-lg font-bold text-white mb-4">Current</h3>
              <div className="space-y-3">
                {members.map((member, index) => (
                  <div
                    key={index}
                    className="bg-[#282828] rounded-lg p-4 hover:bg-[#3e3e3e] transition-colors"
                  >
                    <div className="flex items-start gap-4">
                      {/* Member photo or initial */}
                      {member.image ? (
                        <img
                          src={member.image}
                          alt={member.name}
                          className="w-12 h-12 rounded-full object-cover flex-shrink-0"
                        />
                      ) : (
                        <div className="w-12 h-12 rounded-full bg-[#1DB954] flex items-center justify-center flex-shrink-0">
                          <span className="text-black font-bold text-lg">
                            {member.name.charAt(0)}
                          </span>
                        </div>
                      )}

                      {/* Member info */}
                      <div className="flex-1 min-w-0">
                        <h4 className="text-white font-bold text-base truncate">
                          {member.name}
                        </h4>
                        <p className="text-[#a7a7a7] text-sm mt-1">
                          {member.role}
                        </p>
                        <p className="text-[#1DB954] text-xs mt-1">
                          {member.years}
                        </p>
                        {member.bio && (
                          <p className="text-[#a7a7a7] text-xs mt-2 line-clamp-2">
                            {member.bio}
                          </p>
                        )}
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            </div>
          )}

          {/* Former Members */}
          {formerMembers && formerMembers.length > 0 && (
            <div>
              <h3 className="text-base md:text-lg font-bold text-white mb-4">Former</h3>
              <div className="space-y-3">
                {formerMembers.map((member, index) => (
                  <div
                    key={index}
                    className="bg-[#282828] rounded-lg p-4 hover:bg-[#3e3e3e] transition-colors opacity-75"
                  >
                    <div className="flex items-start gap-4">
                      {/* Member photo or initial */}
                      {member.image ? (
                        <img
                          src={member.image}
                          alt={member.name}
                          className="w-12 h-12 rounded-full object-cover flex-shrink-0 grayscale"
                        />
                      ) : (
                        <div className="w-12 h-12 rounded-full bg-[#535353] flex items-center justify-center flex-shrink-0">
                          <span className="text-white font-bold text-lg">
                            {member.name.charAt(0)}
                          </span>
                        </div>
                      )}

                      {/* Member info */}
                      <div className="flex-1 min-w-0">
                        <h4 className="text-white font-bold text-base truncate">
                          {member.name}
                        </h4>
                        <p className="text-[#a7a7a7] text-sm mt-1">
                          {member.role}
                        </p>
                        <p className="text-[#a7a7a7] text-xs mt-1">
                          {member.years}
                        </p>
                        {member.bio && (
                          <p className="text-[#a7a7a7] text-xs mt-2 line-clamp-2">
                            {member.bio}
                          </p>
                        )}
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            </div>
          )}
        </div>
      </section>
    );
  }

  // Metro/Time Machine style
  if (isMetro) {
    return (
      <section className="mb-8 md:mb-12">
        <div className="flex justify-between items-center mb-4 md:mb-6 pb-3 md:pb-4 border-b border-[#d4d0c8]">
          <h2 className="font-display text-base md:text-lg font-bold text-[#1a1a1a]">
            Band Members
          </h2>
        </div>

        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 md:gap-8">
          {/* Current Members */}
          {members && members.length > 0 && (
            <div>
              <h3 className="font-display text-sm font-bold text-[#1a1a1a] mb-4 pb-2 border-b border-[#e8e4dc]">
                Current
              </h3>
              <div className="space-y-4">
                {members.map((member, index) => (
                  <div
                    key={index}
                    className="bg-white border border-[#d4d0c8] p-4 hover:border-[#e85d04] transition-colors"
                  >
                    <div className="flex items-start gap-4">
                      {/* Member photo or initial */}
                      {member.image ? (
                        <img
                          src={member.image}
                          alt={member.name}
                          className="w-16 h-16 border border-[#d4d0c8] object-cover flex-shrink-0"
                        />
                      ) : (
                        <div className="w-16 h-16 bg-[#e8e4dc] border border-[#d4d0c8] flex items-center justify-center flex-shrink-0">
                          <span className="font-display text-2xl font-bold text-[#6b6b6b]">
                            {member.name.charAt(0)}
                          </span>
                        </div>
                      )}

                      {/* Member info */}
                      <div className="flex-1 min-w-0">
                        <h4 className="font-display text-base font-bold text-[#1a1a1a] mb-1">
                          {member.name}
                        </h4>
                        <p className="text-[#6b6b6b] text-sm mb-1">
                          {member.role}
                        </p>
                        <p className="text-[#e85d04] text-xs font-bold mb-2">
                          {member.years}
                        </p>
                        {member.bio && (
                          <p className="text-[#6b6b6b] text-xs leading-relaxed line-clamp-2">
                            {member.bio}
                          </p>
                        )}
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            </div>
          )}

          {/* Former Members */}
          {formerMembers && formerMembers.length > 0 && (
            <div>
              <h3 className="font-display text-sm font-bold text-[#1a1a1a] mb-4 pb-2 border-b border-[#e8e4dc]">
                Former
              </h3>
              <div className="space-y-4">
                {formerMembers.map((member, index) => (
                  <div
                    key={index}
                    className="bg-white border border-[#d4d0c8] p-4 opacity-80"
                  >
                    <div className="flex items-start gap-4">
                      {/* Member photo or initial */}
                      {member.image ? (
                        <img
                          src={member.image}
                          alt={member.name}
                          className="w-16 h-16 border border-[#d4d0c8] object-cover flex-shrink-0 grayscale"
                        />
                      ) : (
                        <div className="w-16 h-16 bg-[#e8e4dc] border border-[#d4d0c8] flex items-center justify-center flex-shrink-0">
                          <span className="font-display text-2xl font-bold text-[#6b6b6b]">
                            {member.name.charAt(0)}
                          </span>
                        </div>
                      )}

                      {/* Member info */}
                      <div className="flex-1 min-w-0">
                        <h4 className="font-display text-base font-bold text-[#1a1a1a] mb-1">
                          {member.name}
                        </h4>
                        <p className="text-[#6b6b6b] text-sm mb-1">
                          {member.role}
                        </p>
                        <p className="text-[#6b6b6b] text-xs mb-2">
                          {member.years}
                        </p>
                        {member.bio && (
                          <p className="text-[#6b6b6b] text-xs leading-relaxed line-clamp-2">
                            {member.bio}
                          </p>
                        )}
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            </div>
          )}
        </div>
      </section>
    );
  }

  // Default Tron/Synthwave style
  return (
    <section className="mb-10 md:mb-16">
      <div className="flex justify-between items-center mb-6 md:mb-8 pb-3 md:pb-4 section-border">
        <h2 className="font-display text-[0.65rem] md:text-xs uppercase tracking-[0.2em] md:tracking-[0.3em] text-text-dim">
          <span className="text-neon-cyan">//</span> Band Members
        </h2>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 md:gap-8">
        {/* Current Members */}
        {members && members.length > 0 && (
          <div>
            <h3 className="font-display text-[0.55rem] md:text-[0.6rem] uppercase tracking-[0.3em] text-neon-cyan mb-4 md:mb-6 flex items-center gap-2">
              <span className="opacity-50">//</span>
              Current
            </h3>
            <div className="space-y-4">
              {members.map((member, index) => (
                <div
                  key={index}
                  className="album-frame p-[2px] hover:shadow-[0_0_20px_rgba(0,255,255,0.3)] transition-all"
                >
                  <div className="bg-dark-900 p-4">
                    <div className="flex items-start gap-4">
                      {/* Member photo or initial */}
                      {member.image ? (
                        <img
                          src={member.image}
                          alt={member.name}
                          className="w-16 h-16 object-cover flex-shrink-0 border-2 border-neon-cyan/30"
                        />
                      ) : (
                        <div className="w-16 h-16 bg-dark-800 border-2 border-neon-cyan/30 flex items-center justify-center flex-shrink-0">
                          <span className="font-display text-2xl font-bold gradient-text">
                            {member.name.charAt(0)}
                          </span>
                        </div>
                      )}

                      {/* Member info */}
                      <div className="flex-1 min-w-0">
                        <h4 className="font-display text-base md:text-lg font-bold text-white uppercase tracking-wider mb-2">
                          {member.name}
                        </h4>
                        <p className="text-text-dim text-sm mb-2">
                          {member.role}
                        </p>
                        <p className="text-neon-orange text-xs font-bold uppercase tracking-widest mb-2">
                          {member.years}
                        </p>
                        {member.bio && (
                          <p className="text-text-dim text-xs leading-relaxed line-clamp-2">
                            {member.bio}
                          </p>
                        )}
                      </div>
                    </div>
                  </div>
                </div>
              ))}
            </div>
          </div>
        )}

        {/* Former Members */}
        {formerMembers && formerMembers.length > 0 && (
          <div>
            <h3 className="font-display text-[0.55rem] md:text-[0.6rem] uppercase tracking-[0.3em] text-neon-magenta mb-4 md:mb-6 flex items-center gap-2">
              <span className="opacity-50">//</span>
              Former
            </h3>
            <div className="space-y-4">
              {formerMembers.map((member, index) => (
                <div
                  key={index}
                  className="album-frame p-[2px] opacity-70 hover:opacity-100 transition-opacity"
                >
                  <div className="bg-dark-900 p-4">
                    <div className="flex items-start gap-4">
                      {/* Member photo or initial */}
                      {member.image ? (
                        <img
                          src={member.image}
                          alt={member.name}
                          className="w-16 h-16 object-cover flex-shrink-0 border-2 border-neon-magenta/30 grayscale"
                        />
                      ) : (
                        <div className="w-16 h-16 bg-dark-800 border-2 border-neon-magenta/30 flex items-center justify-center flex-shrink-0">
                          <span className="font-display text-2xl font-bold text-neon-magenta">
                            {member.name.charAt(0)}
                          </span>
                        </div>
                      )}

                      {/* Member info */}
                      <div className="flex-1 min-w-0">
                        <h4 className="font-display text-base md:text-lg font-bold text-white uppercase tracking-wider mb-2">
                          {member.name}
                        </h4>
                        <p className="text-text-dim text-sm mb-2">
                          {member.role}
                        </p>
                        <p className="text-text-dim text-xs uppercase tracking-widest mb-2">
                          {member.years}
                        </p>
                        {member.bio && (
                          <p className="text-text-dim text-xs leading-relaxed line-clamp-2">
                            {member.bio}
                          </p>
                        )}
                      </div>
                    </div>
                  </div>
                </div>
              ))}
            </div>
          </div>
        )}
      </div>
    </section>
  );
}
